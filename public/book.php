<?php require_once __DIR__.'/../src/bootstrap.php';$u=require_role('student');verify_csrf();$room=(int)($_POST['room_id']??0);$roommate_request=trim($_POST['roommate_request']??'');
try{$pdo=db();$pdo->beginTransaction();$s=$pdo->prepare('SELECT * FROM rooms WHERE id=? FOR UPDATE');$s->execute([$room]);$r=$s->fetch();if(!$r||$r['status']!=='available'||$r['occupied']>=$r['capacity']) throw new RuntimeException('Room is no longer available.');
$s=$pdo->prepare('SELECT id FROM bookings WHERE student_id=? AND status IN("pending","approved","paid","checked_in")');$s->execute([$u['id']]);if($s->fetch()) throw new RuntimeException('You already have an active booking.');
$pdo->prepare('INSERT INTO bookings(student_id,room_id,status,roommate_request) VALUES(?,?,"approved",?)')->execute([$u['id'],$room,$roommate_request]);
$booking_id = $pdo->lastInsertId();
$pdo->commit();
send_email($u['email'], 'Booking Initiated', "<p>Hi {$u['name']},</p><p>You have initiated a booking for Room {$r['room_number']}. Please complete your payment to secure the room.</p>");
log_event('booking_created',['student_id'=>$u['id'],'room_id'=>$room]);
flash('success','Proceed to payment to secure your room.');
redirect("checkout.php?id=$booking_id");
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());redirect('dashboard.php');}