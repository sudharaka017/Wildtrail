<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('driver');
$st=$pdo->prepare("SELECT b.*,p.name,ps.label,u.full_name tourist,v.make_model,v.plate_number FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id JOIN users u ON u.id=b.visitor_id LEFT JOIN vehicles v ON v.id=b.vehicle_id WHERE b.driver_id=? AND b.status IN('pending','confirmed','completed') ORDER BY b.entry_date");
$st->execute([$_SESSION['user_id']]);
$rows=$st->fetchAll();
dashboard_top('Assigned safaris','Driver workspace'); back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Your dispatch board.</h1>
<p class="section-copy">Your verified jeep is offered only when your availability calendar is open. New reservations appear here immediately; paid reservations become confirmed safaris. Keep your availability updated before tourists book.</p>
<div class="panel table-wrap"><table class="data-table"><tr><th>Booking</th><th>Route</th><th>Guests</th><th>Vehicle</th><th>Safari-day contact</th><th>Status</th></tr>
<?php if(!$rows): ?><tr><td colspan="6"><strong>No assigned safaris yet. Publish your monthly availability so verified jeeps can be booked.</strong><br><a class="text-link" href="availability.php">Set availability →</a></td></tr><?php endif; ?>
<?php foreach($rows as $r): ?><tr>
<td><?=e($r['booking_code'])?><br><span class="small"><?=e($r['tourist'])?></span></td>
<td><?=e($r['name'])?><br><?=e($r['entry_date'])?> · <?=e($r['label'])?></td>
<td><?=e($r['guests'])?></td>
<td><?=e($r['make_model'])?><br><span class="small"><?=e($r['plate_number'])?></span></td><td><?=e($r['pickup_location'])?><br><span class="small"><?=e($r['contact_phone'])?></span><?php if(!empty($r['special_notes'])):?><br><span class="small">Note: <?=e($r['special_notes'])?></span><?php endif;?></td>
<td><span class="badge <?=e($r['status'])?>"><?=e($r['status'])?></span><br><span class="small"><?=e($r['payment_status'])?></span></td>
</tr><?php endforeach; ?></table></div>
<?php dashboard_bottom(); ?>
