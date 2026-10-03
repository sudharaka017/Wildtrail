<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');
if (isset($_POST['cancel'])) {
    $id = (int)$_POST['cancel'];
    $reason = trim($_POST['reason'] ?? 'Changed travel plans');
    if ($reason === '' || strlen($reason) > 255) {
        $reason = 'Changed travel plans';
    }

    $check = $pdo->prepare("SELECT amount,payment_status,booking_code,guide_id,driver_id FROM bookings WHERE id=? AND visitor_id=? AND entry_date>CURDATE() AND status IN('pending','confirmed')");
    $check->execute([$id, $_SESSION['user_id']]);
    $cancelBooking = $check->fetch();
    $refundAmount = ($cancelBooking && $cancelBooking['payment_status'] === 'paid') ? round((float)$cancelBooking['amount'] * CANCELLATION_REFUND_RATE, 2) : 0.00;
    $cancellationFee = ($cancelBooking && $cancelBooking['payment_status'] === 'paid') ? round((float)$cancelBooking['amount'] - $refundAmount, 2) : 0.00;

    $cancel = $pdo->prepare("UPDATE bookings SET status='cancelled',cancellation_reason=?,refund_status=IF(payment_status='paid','pending','none'),refund_amount=?,cancellation_fee=? WHERE id=? AND visitor_id=? AND entry_date>CURDATE() AND status IN('pending','confirmed')");
    $cancel->execute([$reason, $refundAmount, $cancellationFee, $id, $_SESSION['user_id']]);

    if ($cancel->rowCount() === 1) {
        if($cancelBooking){ $n=$pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)'); if($cancelBooking['guide_id'])$n->execute([$cancelBooking['guide_id'],"Safari {$cancelBooking['booking_code']} was cancelled by the tourist."]); if($cancelBooking['driver_id'])$n->execute([$cancelBooking['driver_id'],"Safari {$cancelBooking['booking_code']} was cancelled by the tourist."]); }
        if ($cancelBooking && $cancelBooking['payment_status'] === 'paid') {
            flash('success', 'Booking cancelled. Under the 50% cancellation policy, LKR '.number_format($refundAmount,2).' is pending refund.');
        } else {
            flash('success', 'Booking cancelled. No refund is due because this reservation was not paid.');
        }
    } else {
        flash('error', 'This booking could not be cancelled. It may already be cancelled, completed, or past its travel date.');
    }
    header('Location: bookings.php');
    exit;
}
$st=$pdo->prepare("SELECT b.*,p.name,ps.label,v.make_model,v.plate_number,gu.full_name guide_name,du.full_name driver_name,r.id review_id FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id LEFT JOIN vehicles v ON v.id=b.vehicle_id LEFT JOIN users gu ON gu.id=b.guide_id LEFT JOIN users du ON du.id=b.driver_id LEFT JOIN reviews r ON r.booking_id=b.id WHERE b.visitor_id=? ORDER BY entry_date DESC");
$st->execute([$_SESSION['user_id']]);
$rows=$st->fetchAll();
dashboard_top('My bookings','Tourist portal');
back_button('../tourist/dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Trips & permits.</h1>
<div class="panel table-wrap">
    <table class="data-table">
        <tr>
            <th>Booking</th>
            <th>Safari</th>
            <th>Field team</th>
            <th>Total</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
<?php if(!$rows): ?><tr><td colspan="6"><strong>No safaris yet.</strong><br><span class="small">Explore a park and book an available field team when you're ready.</span><br><a class="text-link" href="book.php">Book a safari →</a></td></tr><?php endif; ?>
<?php
foreach($rows as $r):
?>
<tr>
    <td>
        <strong>
<?=
e($r['booking_code'])
?>
</strong>
<br>
<span class="small">
<?=
e($r['entry_date'])
?>
·
<?=
e($r['label'])
?>
</span>
</td>
<td>
<?=
e($r['name'])
?>
<br>
<span class="small">
<?=
e($r['guests'])
?>
guests · pickup
<?=
e($r['pickup_location'])
?>
</span>
</td>
<td>
<?=
e($r['guide_name']?:'Pending guide')
?>
<br>
<span class="small">
<?=
e($r['make_model']?:'Pending jeep')
?>
·
<?=
e($r['driver_name']?:'')
?>
</span>
</td>
<td>LKR
<?=
number_format($r['amount'],0)
?>
</td>
<td>
    <span class="badge
<?=
e($r['status'])
?>
">
<?=
e($r['status'])
?>
</span>
<br>
<span class="small">
<?=
e($r['payment_status'])
?>
</span>

</td>
<td>
<?php
if($r['payment_status']==='paid' && $r['status']!=='cancelled'):
?>
<a class="text-link" href="permit.php?id=<?=$r['id']?>">Permit / QR</a>
<br>
<?php
elseif($r['payment_status']==='paid' && $r['status']==='cancelled'):
?>
<span class="small">Cancelled · refund <?= e($r['refund_status'] ?? 'pending') ?> · LKR <?= number_format((float)($r['refund_amount'] ?? 0),2) ?></span><br><span class="small">50% refund · 50% cancellation charge</span><br>
<?php
elseif($r['payment_status']==='unpaid' && $r['status']!=='cancelled'):
?>
<a class="text-link" href="payment.php?id=<?=$r['id']?>">Pay now</a>
<br><span class="small">Guide & jeep held for 15 minutes · payment required</span><br>
<?php endif ?>
<?php
if($r['status']==='completed'):
?>
<a class="text-link" href="review.php?id=<?=$r['id']?>">
<?=
$r['review_id']?'Edit review':'Rate safari'
?>
</a>
<?php
elseif(in_array($r['status'],['pending','confirmed'],true)):
?>
<form method="post" class="cancel-form">
<?=
csrf_field()
?>
<input type="hidden" name="cancel" value="<?=$r['id']?>">
<input name="reason" maxlength="255" placeholder="Cancellation reason">
<button class="btn ghost" data-confirm="<?php if($r['payment_status']==='paid'): ?>Cancel this safari? You paid LKR <?= number_format((float)$r['amount'],2) ?>. Refund (50%): LKR <?= number_format((float)$r['amount']*CANCELLATION_REFUND_RATE,2) ?>. Cancellation charge (50%): LKR <?= number_format((float)$r['amount']*(1-CANCELLATION_REFUND_RATE),2) ?>.<?php else: ?>Cancel this unpaid reservation?<?php endif; ?>">Cancel</button>
</form>
<?php
endif
?>
</td>
</tr>
<?php
endforeach
?>
</table>
</div>
<?php
dashboard_bottom();
?>
