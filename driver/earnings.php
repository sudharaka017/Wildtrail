<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('driver');
$st=$pdo->prepare("SELECT b.booking_code,b.entry_date,p.name,b.vehicle_fee_component earning FROM bookings b JOIN parks p ON p.id=b.park_id WHERE b.driver_id=? AND b.payment_status='paid' AND b.status IN('confirmed','completed') ORDER BY b.entry_date DESC");
$st->execute([$_SESSION['user_id']]);
$rows=$st->fetchAll();
dashboard_top('Earnings','Driver workspace');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Earnings history.</h1>
<div class="panel">
    <table class="data-table">
        <tr>
            <th>Booking</th>
            <th>Park</th>
            <th>Date</th>
            <th>Vehicle fee earned</th>
        </tr>
<?php
foreach($rows as $r):
?>
<tr>
    <td>
<?=
e($r['booking_code'])
?>
</td>
<td>
<?=
e($r['name'])
?>
</td>
<td>
<?=
e($r['entry_date'])
?>
</td>
<td>LKR
<?=
number_format($r['earning'],2)
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
