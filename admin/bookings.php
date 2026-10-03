<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_refunded'])){
    $id=(int)$_POST['mark_refunded'];
    $q=$pdo->prepare("SELECT visitor_id,booking_code,refund_amount FROM bookings WHERE id=? AND status='cancelled' AND payment_status='paid' AND refund_status='pending'");$q->execute([$id]);$b=$q->fetch();
    if($b){$pdo->prepare("UPDATE bookings SET payment_status='refunded',refund_status='refunded' WHERE id=?")->execute([$id]);$pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)')->execute([$b['visitor_id'],"Refund of LKR ".number_format((float)$b['refund_amount'],2)." for {$b['booking_code']} has been marked completed by WildTrail staff."]);flash('success','50% refund of LKR '.number_format((float)$b['refund_amount'],2).' marked completed.');}
    else flash('error','This booking is not waiting for refund processing.');
    header('Location: bookings.php');exit;
}
$rows=$pdo->query("SELECT b.*,p.name park,ps.label slot_label,u.full_name tourist,gu.full_name guide_name,du.full_name driver_name,v.plate_number FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id JOIN users u ON u.id=b.visitor_id LEFT JOIN users gu ON gu.id=b.guide_id LEFT JOIN users du ON du.id=b.driver_id LEFT JOIN vehicles v ON v.id=b.vehicle_id ORDER BY b.created_at DESC")->fetchAll();
dashboard_top('Safari bookings','Operations control'); back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Safari booking monitor.</h1>
<p class="section-copy">Bookings no longer wait for manual approval. WildTrail only offers staff-verified guides and jeeps, checks date/slot conflicts in real time, reserves the selected resources, and confirms the safari after PayHere payment. Use this page for monitoring and support.</p>
<div class="panel table-wrap"><table class="data-table"><tr><th>Booking</th><th>Safari</th><th>Tourist</th><th>Verified field team</th><th>Booking / payment</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><strong><?=e($r['booking_code'])?></strong><br><span class="small"><?=e($r['created_at'])?></span></td>
<td><?=e($r['park'])?><br><span class="small"><?=e($r['entry_date'])?> · <?=e($r['slot_label'])?> · <?=e($r['guests'])?> guests</span></td>
<td><?=e($r['tourist'])?></td>
<td><?=e($r['guide_name']?:'—')?><br><span class="small"><?=e($r['plate_number']?:'—')?> · <?=e($r['driver_name']?:'—')?></span></td>
<td><strong><?=e(ucfirst($r['status']))?></strong><br><span class="small"><?=e($r['payment_status'])?><?php if(($r['refund_status']??'none')!=='none'): ?> · refund <?=e($r['refund_status'])?><?php endif; ?></span><?php if(($r['refund_status']??'none')!=='none'): ?><br><span class="small">Refund amount: LKR <?=number_format((float)($r['refund_amount']??0),2)?> · cancellation fee: LKR <?=number_format((float)($r['cancellation_fee']??0),2)?></span><?php endif; ?><?php if(($r['refund_status']??'none')==='pending'): ?><form method="post" style="margin-top:8px"><?=csrf_field()?><button class="btn ghost" name="mark_refunded" value="<?=$r['id']?>" data-confirm="Mark this refund as completed after processing it through the payment provider?">Mark refund completed</button></form><?php endif; ?></td>
</tr><?php endforeach; ?></table></div>
<?php dashboard_bottom(); ?>
