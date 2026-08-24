# Deployment Guide (AWS MVP)

This guide walks through deploying the HostelCloud MVP to an AWS environment.

## 1. Cloud Infrastructure Setup
1. **EC2 Instance:** Launch an Ubuntu 24.04 (or similar) EC2 instance.
2. **Security Group:** Open ports `80` (HTTP), `443` (HTTPS), and `22` (SSH for your IP only).
3. **RDS Database:** Provision a MySQL RDS instance (Free Tier eligible is fine for the MVP).
4. **S3 Bucket:** Create an S3 bucket (e.g., `hostel-cloud-uploads-yourname`) and leave Block Public Access on. We'll use IAM roles for access.
5. **IAM Role:** Create an IAM Role with `AmazonS3FullAccess` (or a restricted policy for just your bucket) and attach it to your EC2 instance.

## 2. Server Configuration
SSH into your EC2 instance and install the required software:

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mysql php-xml php-curl php-mbstring zip unzip
```

Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 3. Application Deployment
Clone your repository into `/var/www/html`:
```bash
cd /var/www/html
sudo rm -rf *
# git clone <your-repo> .
```

Install PHP dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

Set up permissions:
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 775 /var/www/html/storage
```

## 4. Environment Configuration
Copy `.env.example` to `.env` and fill it out:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-ec2-ip-or-domain
DB_HOST=your-rds-endpoint.amazonaws.com
DB_PORT=3306
DB_DATABASE=hostel_cloud
DB_USERNAME=admin
DB_PASSWORD=your_secure_password
STORAGE_DRIVER=s3
S3_BUCKET=your-bucket-name
AWS_REGION=us-east-1
# AWS keys can be empty if you attached an IAM Role to your EC2 instance
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

## 5. Apache Document Root
Edit your Apache configuration to point to the `public/` directory:
```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```
Change `DocumentRoot /var/www/html` to `DocumentRoot /var/www/html/public`.

Restart Apache:
```bash
sudo systemctl restart apache2
```

## 6. CloudWatch Logging (Optional but Recommended)
Install the CloudWatch agent on your EC2 instance and configure it to monitor `/var/www/html/storage/logs/app.log`. This ensures you can monitor application events remotely without SSHing into the server.
