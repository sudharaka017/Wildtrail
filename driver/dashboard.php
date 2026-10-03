<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('driver');
$u=$_SESSION['user_id'];
$s=$pdo->prepare("SELECT COUNT(*) assignments,SUM(status='confirmed') confirmed,COALESCE(SUM(CASE WHEN payment_status='paid' AND status IN('confirmed','completed') THEN vehicle_fee_component ELSE 0 END),0) earnings FROM bookings WHERE driver_id=?");
$s->execute([$u]);
$k=$s->fetch();
dashboard_top('Driver / operator','Driver workspace');
?>
<div class="eyebrow">Run the route</div>
<h1 class="dash-title">Driver / operator</h1>
<div class="grid-3">
    <div class="kpi">Assigned safaris<strong>
<?=
e($k['assignments'])
?>
</strong>
</div>
<div class="kpi">Confirmed<strong>
<?=
e($k['confirmed'])
?>
</strong>
</div>
<div class="kpi">Estimated earnings<strong>LKR
<?=
number_format($k['earnings'],0)
?>
</strong>
</div>
</div>
<div class="panel">
    <h2>Keep the wheels moving.</h2>
    <p>Use Driver profile for your personal/professional details. Use Vehicle & documents for jeep compliance and park assignments, then publish monthly availability and monitor assigned safaris.</p>
</div>
<?php
dashboard_bottom();
?>
