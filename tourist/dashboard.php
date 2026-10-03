<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');
$uid=$_SESSION['user_id'];
$st=$pdo->prepare("SELECT COUNT(*) total,SUM(status='confirmed') confirmed,SUM(payment_status='paid') paid FROM bookings WHERE visitor_id=?");
$st->execute([$uid]);
$k=$st->fetch();
$st=$pdo->prepare("SELECT b.*,p.name,ps.label FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id WHERE visitor_id=? ORDER BY b.created_at DESC LIMIT 5");
$st->execute([$uid]);
$rows=$st->fetchAll();
$un=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');$un->execute([$uid]);$unread=(int)$un->fetchColumn();
dashboard_top('Tourist dashboard','Your safari notebook');
?>
<div class="eyebrow">Tourist portal</div>
<h1 class="dash-title">Plan better days in the wild.</h1><p><a class="text-link" href="notifications.php">Notifications<?= $unread ? ' · '.$unread.' new' : '' ?></a></p>
<div class="grid-3">
    <div class="kpi">Bookings<strong>
<?=
e($k['total']??0)
?>
</strong>
</div>
<div class="kpi">Confirmed<strong>
<?=
e($k['confirmed']??0)
?>
</strong>
</div>
<div class="kpi">Paid<strong>
<?=
e($k['paid']??0)
?>
</strong>
</div>
</div>
<div class="panel">
    <h2>Recent bookings</h2>
    <div class="table-wrap">
        <table class="data-table">
            <tr>
                <th>Code</th>
                <th>Park</th>
                <th>Date / slot</th>
                <th>Status</th>
                <th>Payment</th>
            </tr>
<?php if(!$rows): ?><tr><td colspan="5"><strong>No bookings yet.</strong><br><a class="text-link" href="book.php">Explore parks and book your first safari →</a></td></tr><?php endif; ?>
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
<br>
<?=
e($r['label'])
?>
</td>
<td>
    <span class="badge
<?=
e($r['status'])
?>
">
<?=
e($r['status'])
?>
</span>
</td>
<td>
<?=
e($r['payment_status'])
?>
</td>
</tr>
<?php
endforeach
?>
</table>
</div>
</div>
<?php
dashboard_bottom();
?>
