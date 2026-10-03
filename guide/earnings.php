<?php
require '../includes/bootstrap.php'; require '../includes/layout.php'; require_role('guide');
$st=$pdo->prepare("SELECT b.booking_code,b.entry_date,p.name,b.guide_fee_component earning FROM bookings b JOIN parks p ON p.id=b.park_id WHERE b.guide_id=? AND b.payment_status='paid' AND b.status IN('confirmed','completed') ORDER BY b.entry_date DESC");
$st->execute([$_SESSION['user_id']]); $rows=$st->fetchAll(); $total=array_sum(array_map(fn($r)=>(float)$r['earning'],$rows));
dashboard_top('Guide earnings','Guide workspace'); back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Guide earnings.</h1><div class="kpi">Paid confirmed/completed safaris<strong>LKR <?=number_format($total,2)?></strong></div>
<div class="panel table-wrap"><table class="data-table"><tr><th>Booking</th><th>Park</th><th>Date</th><th>Guide fee</th></tr>
<?php if(!$rows): ?><tr><td colspan="4">No paid safari earnings yet.</td></tr><?php endif; ?>
<?php foreach($rows as $r): ?><tr><td><?=e($r['booking_code'])?></td><td><?=e($r['name'])?></td><td><?=e($r['entry_date'])?></td><td>LKR <?=number_format((float)$r['earning'],2)?></td></tr><?php endforeach; ?></table></div>
<?php dashboard_bottom(); ?>
