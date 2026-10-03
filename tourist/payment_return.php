<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');
$id=(int)($_GET['id']??0);
$st=$pdo->prepare('SELECT * FROM bookings WHERE id=? AND visitor_id=?');
$st->execute([$id,$_SESSION['user_id']]);
$b=$st->fetch();
if(!$b) exit('Booking not found.');
if($b['payment_status']==='paid') {
    flash('success','Payment confirmed. Your digital permit is ready.');
    header('Location: booking-success.php?id='.$id);
    exit;
}
dashboard_top('Payment status','Tourist portal');
back_button('bookings.php','Back to my bookings');
?>
<h1 class="dash-title">Checking your payment.</h1>
<div class="panel">
    <h2>Waiting for secure confirmation</h2>
    <p>PayHere has returned you to WildTrail Lanka. The booking becomes <strong>Confirmed</strong> only after WildTrail receives and verifies PayHere’s server notification.</p>
    <p class="small">This page checks again automatically. If you are testing on localhost, use WildTrail Local Demo mode or expose the site through a public HTTPS URL so PayHere can reach the callback.</p>
    <a class="btn" href="payment_return.php?id=<?=$id?>">Check now</a>
    <script>setTimeout(()=>location.reload(),4000);</script>
</div>
<?php
dashboard_bottom();
?>
