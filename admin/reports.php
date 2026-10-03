<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
if(isset($_GET['export'])) {
    header('Content-Type:text/csv');
    header('Content-Disposition:attachment; filename="wildtrail-bookings.csv"');
    $o=fopen('php://output','w');
    fputcsv($o,['Booking','Park','Date','Guests','Amount','Payment','Status']);
    $q=$pdo->query("SELECT b.booking_code,p.name,b.entry_date,b.guests,b.amount,b.payment_status,b.status FROM bookings b JOIN parks p ON p.id=b.park_id ORDER BY b.entry_date");
    foreach($q as $r)fputcsv($o,$r);
    fclose($o);
    exit;
}
$rows=$pdo->query("SELECT p.name,p.province,p.daily_vehicle_cap,COUNT(b.id) bookings,COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.amount ELSE 0 END),0) revenue,(SELECT COUNT(*) FROM wildlife_sightings w WHERE w.park_id=p.id) sightings FROM parks p LEFT JOIN bookings b ON b.park_id=p.id GROUP BY p.id ORDER BY p.name")->fetchAll();
dashboard_top('Reports','Operations control');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Operational reporting.</h1>
<a class="btn" href="?export=1">Export bookings CSV</a>
<div class="panel">
    <table class="data-table">
        <tr>
            <th>Park</th>
            <th>Bookings</th>
            <th>Paid revenue</th>
            <th>Sightings</th>
            <th>Daily cap</th>
        </tr>
<?php
foreach($rows as $r):
?>
<tr>
    <td>
<?=
e($r['name'])
?>
<br>
<span class="small">
<?=
e($r['province'])
?>
</span>
</td>
<td>
<?=
e($r['bookings'])
?>
</td>
<td>LKR
<?=
number_format($r['revenue'],0)
?>
</td>
<td>
<?=
e($r['sightings'])
?>
</td>
<td>
<?=
e($r['daily_vehicle_cap'])
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
