<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
$k=$pdo->query("SELECT (SELECT COUNT(*) FROM bookings) bookings,(SELECT COUNT(*) FROM users WHERE status='pending') pending_users,(SELECT COALESCE(SUM(amount),0) FROM bookings WHERE payment_status='paid') revenue,(SELECT COUNT(*) FROM wildlife_sightings WHERE review_status='flagged') flagged")->fetch();
dashboard_top('Park administration','Operations control');
?>
<div class="eyebrow">DWC / administration</div>
<h1 class="dash-title">One view of the network.</h1>
<div class="grid-3">
    <div class="kpi">Bookings<strong>
<?=
e($k['bookings'])
?>
</strong>
</div>
<div class="kpi">Pending user checks<strong>
<?=
e($k['pending_users'])
?>
</strong>
</div>
<div class="kpi">Paid revenue<strong>LKR
<?=
number_format($k['revenue'],0)
?>
</strong>
</div>
</div>
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
        <div>
            <h2 style="margin-bottom:6px">Wildlife movement map</h2>
            <p>Review mapped sightings, density and protected-species activity.</p>
        </div>
        <a class="btn" href="heatmap.php">Open heatmap</a>
    </div>
</div>
<div class="panel">
    <h2>Conservation alerts</h2>
    <p>
        <strong>
<?=
e($k['flagged'])
?>
</strong> protected-species sighting(s) are waiting for review.</p>
</div>
<?php
dashboard_bottom();
?>
