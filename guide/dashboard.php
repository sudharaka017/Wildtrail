<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('guide');
$u=$_SESSION['user_id'];
$st=$pdo->prepare("SELECT (SELECT COUNT(*) FROM bookings WHERE guide_id=?) assignments,(SELECT COUNT(*) FROM wildlife_sightings WHERE guide_id=?) sightings,(SELECT COUNT(*) FROM guide_availability WHERE guide_id=? AND available_date>=CURDATE() AND is_available=1) open_slots");
$st->execute([$u,$u,$u]);
$k=$st->fetch();
dashboard_top('Licensed guide','Guide workspace');
?>
<div class="eyebrow">Lead the field</div>
<h1 class="dash-title">Licensed guide</h1>
<div class="grid-3">
    <div class="kpi">Assignments<strong>
<?=
e($k['assignments'])
?>
</strong>
</div>
<div class="kpi">Field contributions<strong>
<?=
e($k['sightings'])
?>
</strong>
</div>
<div class="kpi">Available times<strong><?=e($k['open_slots']??0)?></strong>
</div>
</div>
<?php
dashboard_bottom();
?>
