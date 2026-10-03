<?php
require '../includes/bootstrap.php';
if(PAYHERE_MODE==='local_demo') {
    http_response_code(204);
    exit;
}
$merchant_id=(string)($_POST['merchant_id']??'');
$order_id=(string)($_POST['order_id']??'');
$payhere_amount=(string)($_POST['payhere_amount']??'');
$payhere_currency=(string)($_POST['payhere_currency']??'');
$status_code=(string)($_POST['status_code']??'');
$md5sig=(string)($_POST['md5sig']??'');
if(!$merchant_id||!$order_id||!$md5sig||!PAYHERE_MERCHANT_SECRET) {
    http_response_code(400);
    exit;
}
$local=strtoupper(md5($merchant_id.$order_id.$payhere_amount.$payhere_currency.$status_code.strtoupper(md5(PAYHERE_MERCHANT_SECRET))));
if(!hash_equals($local,$md5sig) || !hash_equals(PAYHERE_MERCHANT_ID,$merchant_id) || $payhere_currency !== PAYHERE_CURRENCY) {
    http_response_code(403);
    exit;
}
if($status_code==='2') {
    $pdo->beginTransaction();
    $q=$pdo->prepare("SELECT id,visitor_id,guide_id,driver_id FROM bookings WHERE booking_code=? AND amount=? AND status='pending' AND payment_status='unpaid' FOR UPDATE");
    $q->execute([$order_id,(float)$payhere_amount]); $row=$q->fetch();
    if($row){
        $pdo->prepare("UPDATE bookings SET payment_status='paid',status='confirmed',payment_ref=?,reserved_until=NULL WHERE id=?")->execute([$_POST['payment_id']??'PAYHERE',$row['id']]);
        $n=$pdo->prepare("INSERT INTO notifications(user_id,message) VALUES(?,?)");
        $n->execute([$row['visitor_id'],"Payment successful. Safari $order_id is confirmed and your QR permit is ready."]);
        if($row['guide_id']) $n->execute([$row['guide_id'],"Safari $order_id is now paid and confirmed."]);
        if($row['driver_id']) $n->execute([$row['driver_id'],"Safari $order_id is now paid and confirmed."]);
    }
    $pdo->commit();
}
http_response_code(204);
