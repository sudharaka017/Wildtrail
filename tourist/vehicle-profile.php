<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');

$id = (int)($_GET['id'] ?? 0);
$returnToBooking = ($_GET['return'] ?? '') === 'booking';

$st = $pdo->prepare("SELECT v.*,u.full_name driver_name,dp.profile_photo driver_photo,dp.bio driver_bio,dp.languages driver_languages,dp.experience_years driver_experience,COALESCE(AVG(r.vehicle_rating),0) rating,COUNT(r.id) reviews FROM vehicles v JOIN users u ON u.id=v.owner_id LEFT JOIN driver_profiles dp ON dp.user_id=u.id LEFT JOIN bookings b ON b.vehicle_id=v.id LEFT JOIN reviews r ON r.booking_id=b.id WHERE v.id=? AND v.approval_status='approved' GROUP BY v.id");
$st->execute([$id]);
$v = $st->fetch();

if (!$v) {
    flash('error', 'That vehicle profile is no longer available.');
    header('Location: ' . ($returnToBooking ? 'book.php?resume=1' : 'book.php'));
    exit;
}

$rv = $pdo->prepare("SELECT r.comment,r.vehicle_rating,r.driver_rating,r.created_at,u.full_name FROM reviews r JOIN bookings b ON b.id=r.booking_id JOIN users u ON u.id=r.visitor_id WHERE b.vehicle_id=? AND r.vehicle_rating IS NOT NULL ORDER BY r.created_at DESC LIMIT 8");
$rv->execute([$id]);
$reviews = $rv->fetchAll();

dashboard_top('Vehicle profile', 'Tourist portal');
back_button($returnToBooking ? 'book.php?resume=1' : 'book.php', 'Back to booking');
?>
<div class="profile-hero">
    <div class="vehicle-profile-photo">
        <?php if (!empty($v['photo'])): ?>
            <img src="../uploads/photos/<?= e($v['photo']) ?>" alt="<?= e($v['make_model']) ?>">
        <?php else: ?>
            <span>4×4</span>
        <?php endif; ?>
    </div>
    <div>
        <div class="eyebrow">Verified safari vehicle</div>
        <h1 class="dash-title"><?= e($v['make_model']) ?></h1>
        <p><?= e($v['plate_number']) ?> · ★ <?= number_format((float)$v['rating'], 1) ?> · <?= (int)$v['reviews'] ?> reviews</p>
    </div>
</div>
<div class="grid-2">
    <div class="panel">
        <h2>Vehicle details</h2>
        <p><strong>Driver / operator</strong><br><?= e($v['driver_name']) ?></p>
        <p><strong>Driver experience</strong><br><?= (int)($v['driver_experience']??0) ?> years</p>
        <p><strong>Driver languages</strong><br><?= e($v['driver_languages'] ?: 'Not specified') ?></p>
        <?php if(!empty($v['driver_bio'])): ?><p><strong>About the driver</strong><br><?= e($v['driver_bio']) ?></p><?php endif; ?>
        <p><strong>Capacity</strong><br><?= (int)$v['capacity'] ?> passengers</p>
        <p><strong>Safari style</strong><br><?= e(ucfirst($v['safari_type'])) ?></p>
        <p><strong>Amenities</strong><br><?= e($v['amenities'] ?: 'Standard safari equipment') ?></p>
        <p><strong>Compliance</strong><br>Registration ✓ · Insurance ✓ · Fitness certificate ✓</p>
    </div>
    <div class="panel">
        <h2>Traveller feedback</h2>
        <?php foreach ($reviews as $r): ?>
            <div class="review">
                <strong>Vehicle ★ <?= e($r['vehicle_rating']) ?> · Driver ★ <?= e($r['driver_rating']) ?></strong>
                <p><?= e($r['comment']) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><p class="small">No published reviews yet.</p><?php endif; ?>
    </div>
</div>
<?php dashboard_bottom(); ?>
