<?php
declare(strict_types=1);
session_start();
$root = dirname(__DIR__);
if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}
$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
function env(string $key, mixed $default=null): mixed { return $_ENV[$key] ?? $default; }
function config(string $key, mixed $default=null): mixed { return env($key, $default); }
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host='.env('DB_HOST','127.0.0.1').';port='.env('DB_PORT','3306').';dbname='.env('DB_DATABASE','hostel_cloud').';charset=utf8mb4';
    $pdo = new PDO($dsn, env('DB_USERNAME','root'), env('DB_PASSWORD',''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Automatically run database migrations if tables are missing
    require_once __DIR__ . '/migrate.php';
    run_migrations($pdo);
    
    return $pdo;
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url(string $path=''): string { 
    $baseUrl = env('APP_URL');
    if (empty($baseUrl) || (isset($_SERVER['HTTP_HOST']) && str_contains($baseUrl, 'localhost') && !str_contains($_SERVER['HTTP_HOST'], 'localhost'))) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://";
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptDir === '/') $scriptDir = '';
        $baseUrl = $protocol . $host . $scriptDir;
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}
function redirect(string $path): never { header('Location: '.url($path)); exit; }
function current_user(): ?array {
    if(!isset($_SESSION['user_id'])) return null;
    $s=db()->prepare('SELECT * FROM users WHERE id=? AND is_suspended=0');
    $s->execute([$_SESSION['user_id']]);
    $u=$s->fetch();
    if(!$u) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $u;
}
function require_login(): array { $u=current_user(); if(!$u) redirect('login.php'); return $u; }
function require_role(array|string $roles): array {
    $u=require_login(); $roles=(array)$roles;
    if (!in_array($u['role'],$roles,true)) { http_response_code(403); exit('Forbidden'); }
    return $u;
}
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) {
        http_response_code(419); exit('Invalid request token');
    }
}
function flash(string $key, ?string $value=null): ?string {
    if ($value!==null) { $_SESSION['flash'][$key]=$value; return null; }
    $v=$_SESSION['flash'][$key]??null; unset($_SESSION['flash'][$key]); return $v;
}
function log_event(string $event, array $context=[]): void {
    $dir=dirname(__DIR__).'/storage/logs'; if(!is_dir($dir)) mkdir($dir,0775,true);
    file_put_contents($dir.'/app.log', json_encode(['time'=>gmdate('c'),'event'=>$event,'context'=>$context]).PHP_EOL, FILE_APPEND|LOCK_EX);
}
function upload_file(array $file, string $prefix='uploads'): ?string {
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) return null;
    if (($file['size']??0)>5*1024*1024) throw new RuntimeException('File exceeds 5 MB');
    $allowed=['image/jpeg','image/png','image/webp','application/pdf'];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if(!in_array($mime,$allowed,true)) throw new RuntimeException('Unsupported file type');
    $name=$prefix.'/'.bin2hex(random_bytes(16)).'.'.pathinfo($file['name'],PATHINFO_EXTENSION);
    if (env('STORAGE_DRIVER','local')==='s3') {
        if (!class_exists(\Aws\S3\S3Client::class)) {
            throw new RuntimeException('S3 SDK adapter is not installed. Run composer require aws/aws-sdk-php');
        }
        $s3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => env('AWS_REGION', 'us-east-1'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);
        $bucket = env('S3_BUCKET');
        if (!$bucket) throw new RuntimeException('S3_BUCKET is not set.');
        
        $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $name,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $mime,
            'ACL'    => 'public-read'
        ]);
        return $s3->getObjectUrl($bucket, $name);
    }
    $dest=dirname(__DIR__).'/public/storage/'.$name;
    if(!is_dir(dirname($dest))) mkdir(dirname($dest),0775,true);
    if(!move_uploaded_file($file['tmp_name'],$dest)) throw new RuntimeException('Upload failed');
    return 'storage/'.$name;
}

function send_email(string $to, string $subject, string $body): bool {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST', '127.0.0.1');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USER', '');
        $mail->Password   = env('SMTP_PASS', '');
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)env('SMTP_PORT', 587);

        $mail->setFrom(env('MAIL_FROM_ADDRESS', 'noreply@hostelcloud.com'), env('MAIL_FROM_NAME', 'Hostel Cloud'));
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // If SMTP isn't configured, log it instead of throwing an error to prevent breaking local testing
        if (empty($mail->Username)) {
            log_event('email_simulated', ['to'=>$to, 'subject'=>$subject]);
            return true;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        log_event('email_failed', ['to'=>$to, 'error'=>$mail->ErrorInfo]);
        return false;
    }
}
