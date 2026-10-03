<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');

$id = (int)($_GET['id'] ?? 0);
$returnToBooking = ($_GET['return'] ?? '') === 'booking';

$st = $pdo->prepare("SELECT u.id,u.full_name,u.avatar_url,g.*,COALESCE(AVG(r.guide_rating),0) rating,COUNT(r.id) reviews FROM users u JOIN guide_profiles g ON g.user_id=u.id LEFT JOIN bookings b ON b.guide_id=u.id LEFT JOIN reviews r ON r.booking_id=b.id WHERE u.id=? AND u.role='guide' AND u.status='active' AND g.verified=1 GROUP BY u.id");
$st->execute([$id]);
$g = $st->fetch();

if (!$g) {
    flash('error', 'That guide profile is no longer available.');
    header('Location: ' . ($returnToBooking ? 'book.php?resume=1' : 'book.php'));
    exit;
}

$rv = $pdo->prepare("SELECT r.comment,r.guide_rating,r.created_at,u.full_name FROM reviews r JOIN bookings b ON b.id=r.booking_id JOIN users u ON u.id=r.visitor_id WHERE b.guide_id=? AND r.guide_rating IS NOT NULL ORDER BY r.created_at DESC LIMIT 8");
$rv->execute([$id]);
$reviews = $rv->fetchAll();

dashboard_top('Guide profile', 'Tourist portal');
back_button($returnToBooking ? 'book.php?resume=1' : 'book.php', 'Back to booking');
?>
<div class="profile-hero">
    <div class="guide-avatar large">
        <?php if (!empty($g['profile_photo'])): ?>
            <img src="../uploads/photos/<?= e($g['profile_photo']) ?>" alt="<?= e($g['full_name']) ?>">
        <?php else: ?>
            <span><?= e(strtoupper(substr($g['full_name'], 0, 1))) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <div class="eyebrow">DWC licence verified</div>
        <h1 class="dash-title"><?= e($g['full_name']) ?></h1>
        <p>★ <?= number_format((float)$g['rating'], 1) ?> · <?= (int)$g['reviews'] ?> reviews · <?= (int)$g['experience_years'] ?> years field experience</p>
    </div>
</div>
<div class="grid-2">
    <div class="panel">
        <h2>About your guide</h2>
        <p><?= nl2br(e($g['bio'])) ?></p>
        <p><strong>Languages</strong><br><?= e($g['languages']) ?></p>
        <p><strong>Field specialties</strong><br><?= e($g['specialties']) ?></p>
        <p><strong>Licence</strong><br><?= e($g['license_no']) ?> · verified</p>
    </div>
    <div class="panel">
        <h2>Traveller feedback</h2>
        <?php foreach ($reviews as $r): ?>
            <div class="review">
                <strong>★ <?= e($r['guide_rating']) ?> · <?= e($r['full_name']) ?></strong>
                <p><?= e($r['comment']) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><p class="small">No published reviews yet.</p><?php endif; ?>
    </div>
</div>
<?php dashboard_bottom(); ?>
