<?php
function nav_html($root='') {
    $logged=!empty($_SESSION['user_id']);
    $role=$_SESSION['user_role']??'';
    $dash=$logged?$root.home_for_role($role):$root.'login.php';
    return '<header class="site-nav"><a class="brand" href="'.$root.'index.php"><span class="mark"><img src="'.$root.'assets/img/wildtrail-logo.svg" alt=""></span><span>WildTrail <em>Lanka</em></span></a><nav><a href="'.$root.'parks.php">Explore parks</a><a href="'.$root.'wildlife.php">Wildlife & impact</a></nav><div class="nav-actions">'.($logged?'<a class="btn ghost" href="'.$dash.'">Dashboard</a><a class="btn" href="'.$root.'logout.php">Sign out</a>':'<a class="btn ghost" href="'.$root.'login.php">Sign in</a><a class="btn" href="'.$root.'register.php">Join / book safari</a>').'</div></header>';
}
function page_top($title,$root='',$bodyClass='') {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Sri Lanka national park safari booking and wildlife operations platform"><title>'.e($title).' · WildTrail Lanka</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,500&display=swap" rel="stylesheet"><link rel="stylesheet" href="'.$root.'assets/css/app.css"></head><body class="'.e($bodyClass).'">'.nav_html($root);
    $f=pull_flash();
    if($f) echo '<div class="flash '.e($f['type']).'">'.e($f['msg']).'</div>';
}
function page_bottom($root='') {
    echo '<footer><div><strong>WildTrail Lanka</strong><span>Conservation-first safari discovery.</span></div><small>Responsible journeys · verified field teams · conservation-aware operations</small></footer><script src="'.$root.'assets/js/app.js"></script></body></html>';
}
function dashboard_top($title,$kicker,$root='../') {
    page_top($title,$root,'dashboard-page');
    echo '<main class="dash-shell"><aside class="dash-side"><div class="side-title">'.e($kicker).'</div><a href="'.$root.home_for_role($_SESSION['user_role']).'">Overview</a>';
    $r=$_SESSION['user_role'];
    if($r==='tourist') echo '<a href="'.$root.'tourist/book.php">Book safari</a><a href="'.$root.'tourist/bookings.php">My bookings</a><a href="'.$root.'tourist/notifications.php">Notifications</a><a href="'.$root.'tourist/profile.php">Profile</a>';
    if($r==='driver') echo '<a href="'.$root.'driver/requests.php">Assigned safaris</a><a href="'.$root.'driver/vehicle.php">Vehicle & documents</a><a href="'.$root.'driver/availability.php">Availability</a><a href="'.$root.'driver/earnings.php">Earnings</a><a href="'.$root.'driver/profile.php">Driver profile</a><a href="'.$root.'driver/notifications.php">Notifications</a>';
    if($r==='guide') echo '<a href="'.$root.'guide/schedule.php">Schedule</a><a href="'.$root.'guide/availability.php">Availability</a><a href="'.$root.'guide/sighting.php">Log sighting</a><a href="'.$root.'guide/earnings.php">Earnings</a><a href="'.$root.'guide/profile.php">Guide profile</a><a href="'.$root.'guide/notifications.php">Notifications</a>';
    if(in_array($r,['admin','superadmin'],true)) echo '<a href="'.$root.'admin/users.php">Users & approvals</a><a href="'.$root.'admin/bookings.php">Bookings</a><a href="'.$root.'admin/permit-verify.php">Verify permit</a><a href="'.$root.'admin/parks.php">Parks & capacity</a><a href="'.$root.'admin/reports.php">Reports</a><a href="'.$root.'admin/heatmap.php">Wildlife heatmap</a><a href="'.$root.'admin/zones.php">Park zones</a>';
    echo '</aside><section class="dash-main">';
}
function back_button($href, $label='Back') {
    echo '<div class="page-back-row"><a class="page-back" href="'.e($href).'" aria-label="'.e($label).'"><span aria-hidden="true">←</span><span>'.e($label).'</span></a></div>';
}

function dashboard_bottom($root='../') {
    echo '</section></main>';
    page_bottom($root);
}
