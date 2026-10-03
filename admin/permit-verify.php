<?php
require '../includes/bootstrap.php'; require '../includes/layout.php'; require_role(['admin','superadmin']);
$code=strtoupper(trim((string)($_GET['code']??$_POST['code']??''))); $b=null;
if($code!==''){
 $st=$pdo->prepare("SELECT b.booking_code,b.entry_date,b.guests,b.status,b.payment_status,p.name park_name,ps.label,u.full_name visitor,v.plate_number,gu.full_name guide_name,du.full_name driver_name FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id JOIN users u ON u.id=b.visitor_id LEFT JOIN vehicles v ON v.id=b.vehicle_id LEFT JOIN users gu ON gu.id=b.guide_id LEFT JOIN users du ON du.id=b.driver_id WHERE b.booking_code=? LIMIT 1"); $st->execute([$code]); $b=$st->fetch();
}
dashboard_top('Verify permit','Operations control'); back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Verify safari permit.</h1><p class="section-copy">Scan the QR with any camera/QR reader to obtain the WildTrail booking code, then enter it here.</p>
<form class="panel" method="get"><div class="field"><label>Booking / QR code</label><input name="code" required maxlength="24" value="<?=e($code)?>" placeholder="WT..."></div><button class="btn">Verify permit</button></form>
<?php if($code!=='' && !$b): ?><div class="panel"><strong>Not valid</strong><p>No booking matches this code.</p></div><?php elseif($b): $valid=$b['payment_status']==='paid' && in_array($b['status'],['confirmed','completed'],true); ?>
<div class="panel"><h2><?=$valid?'✓ Valid paid permit':'⚠ Permit not currently valid'?></h2><div class="summary-lines"><span>Booking</span><strong><?=e($b['booking_code'])?></strong><span>Visitor</span><strong><?=e($b['visitor'])?></strong><span>Park</span><strong><?=e($b['park_name'])?></strong><span>Date / safari time</span><strong><?=e($b['entry_date'])?> · <?=e($b['label'])?></strong><span>Guests</span><strong><?=e($b['guests'])?></strong><span>Guide</span><strong><?=e($b['guide_name']?:'—')?></strong><span>Vehicle / driver</span><strong><?=e(($b['plate_number']?:'—').' · '.($b['driver_name']?:'—'))?></strong><span>Status</span><strong><?=e($b['status'])?> · <?=e($b['payment_status'])?></strong></div></div>
<?php endif; ?>
<?php dashboard_bottom(); ?>
