<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');

use WildTrail\Support\QrCodeV1;

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT b.*,p.name,ps.label,ps.start_time,ps.end_time,v.make_model,v.plate_number,du.full_name driver_name,gu.full_name guide_name FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id LEFT JOIN vehicles v ON v.id=b.vehicle_id LEFT JOIN users du ON du.id=b.driver_id LEFT JOIN users gu ON gu.id=b.guide_id WHERE b.id=? AND visitor_id=? AND b.payment_status='paid' AND b.status IN('confirmed','completed')");
$st->execute([$id, $_SESSION['user_id']]);
$b = $st->fetch();

if (!$b) {
    flash('error', 'A valid paid permit was not found for this booking.');
    header('Location: bookings.php');
    exit;
}

$qrSvg = QrCodeV1::svg($b['booking_code'], 7, 4);

dashboard_top('E-permit', 'Tourist portal');
back_button('bookings.php', 'Back to my bookings');
?>
<div class="panel permit-card" id="permit">
    <div class="permit-main">
        <div class="eyebrow">Digital e-permit</div>
        <h1 class="dash-title"><?= e($b['booking_code']) ?></h1>
        <h2><?= e($b['name']) ?></h2>

        <div class="permit-details">
            <p><strong>Date:</strong> <?= e($b['entry_date']) ?> · <strong>Safari time:</strong> <?= e($b['label']) ?> · <strong>Guests:</strong> <?= e($b['guests']) ?></p>
            <p><strong>Guide:</strong> <?= e($b['guide_name']) ?> · <strong>Vehicle:</strong> <?= e($b['make_model']) ?> (<?= e($b['plate_number']) ?>)</p>
            <p><strong>Driver:</strong> <?= e($b['driver_name']) ?> · <strong>Meeting point:</strong> <?= e($b['pickup_location']) ?></p>
            <p><strong>Payment:</strong> <?= e($b['payment_ref']) ?></p>
            <p><span class="badge">Paid & confirmed</span></p>
        </div>

        <button class="btn permit-print" type="button" onclick="window.print()">Print / save as PDF</button>
    </div>

    <aside class="permit-qr-box">
        <div class="permit-qr"><?= $qrSvg ?></div>
        <strong>Scan permit code</strong>
        <span><?= e($b['booking_code']) ?></span>
        <small>Offline QR — no internet or CDN required.</small>
    </aside>
</div>
<?php dashboard_bottom(); ?>
