<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('guide');
$st=$pdo->prepare("SELECT b.*,p.name,ps.label,ps.start_time,u.full_name tourist FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id JOIN users u ON u.id=b.visitor_id WHERE guide_id=? AND b.status IN('pending','confirmed','completed') ORDER BY entry_date");
$st->execute([$_SESSION['user_id']]);
$rows=$st->fetchAll();
dashboard_top('Schedule','Guide workspace');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Know the next track.</h1>
<div class="panel">
    <table class="data-table">
        <tr>
            <th>Date</th>
            <th>Park</th>
            <th>Booking</th>
            <th>Guests</th><th>Safari-day contact</th><th>Status</th>
        </tr>
<?php if(!$rows): ?><tr><td colspan="6"><strong>No safaris on your schedule yet. Publish your monthly availability to appear in tourist searches.</strong><br><a class="text-link" href="availability.php">Set availability →</a></td></tr><?php endif; ?>
<?php
foreach($rows as $r):
?>
<tr>
    <td>
<?=
e($r['entry_date'])
?>
<br>
<?=
substr($r['start_time'],0,5)
?>
</td>
<td>
<?=
e($r['name'])
?>
</td>
<td>
<?=
e($r['booking_code'])
?>
<br>
<span class="small">
<?=
e($r['tourist'])
?>
</span>
</td>
<td>
<?=
e($r['guests'])
?>
</td><td><?=e($r['pickup_location'])?><br><span class="small"><?=e($r['contact_phone'])?></span><?php if(!empty($r['special_notes'])):?><br><span class="small">Note: <?=e($r['special_notes'])?></span><?php endif;?></td><td><?=e($r['status'])?><br><span class="small"><?=e($r['payment_status'])?></span></td>
</tr>
<?php
endforeach
?>
</table>
</div>
<?php
dashboard_bottom();
?>
