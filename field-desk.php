<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';
page_top('Field desk');
?>
<section class="section">
    <h1 class="display" style="font-size:78px;color:var(--ink)">The field <em>desk.</em>
    </h1>
    <p class="lead" style="color:var(--muted)">The operational workspace is protected. Drivers manage vehicles and requests, guides manage schedules and sightings, while park administrators manage approvals, capacity and reporting.</p>
    <div class="grid-3" style="margin-top:35px">
        <div class="panel">
            <h2>Driver / operator</h2>
            <p>Upload compliance documents, select operating parks, publish availability and track your assigned paid safaris and earnings.</p>
            <a class="btn" href="login.php">Open workspace</a>
        </div>
        <div class="panel">
            <h2>Licensed guide</h2>
            <p>See assignments, update availability and submit structured wildlife sightings.</p>
            <a class="btn" href="login.php">Open workspace</a>
        </div>
        <div class="panel">
            <h2>Park admin</h2>
            <p>Approve accounts, monitor bookings and vehicle limits, manage parks and export reports.</p>
            <a class="btn" href="login.php">Open workspace</a>
        </div>
    </div>
</section>
<?php
page_bottom();
?>
