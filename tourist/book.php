<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');

use WildTrail\Exceptions\BookingException;
use WildTrail\Repositories\GuideRepository;
use WildTrail\Repositories\ParkRepository;
use WildTrail\Repositories\VehicleRepository;
use WildTrail\Services\BookingService;
use WildTrail\Support\BookingDraft;

$parksRepo = new ParkRepository($pdo);
$vehiclesRepo = new VehicleRepository($pdo);
$guidesRepo = new GuideRepository($pdo);
$bookingService = new BookingService($pdo, $vehiclesRepo, $guidesRepo);
$draftStore = new BookingDraft();
$parks = $parksRepo->active();

function booking_input(array $source): array
{
    return [
        'park_id' => (int)($source['park_id'] ?? $source['park'] ?? 0),
        'date' => trim((string)($source['date'] ?? '')),
        'slot_id' => (int)($source['slot_id'] ?? $source['slot'] ?? 0),
        'guests' => max(1, (int)($source['guests'] ?? 2)),
        'preferred_language' => trim((string)($source['preferred_language'] ?? $source['language'] ?? '')),
        'vehicle_id' => (int)($source['vehicle_id'] ?? 0),
        'guide_id' => (int)($source['guide_id'] ?? 0),
        'pickup_location' => trim((string)($source['pickup_location'] ?? '')),
        'contact_phone' => trim((string)($source['contact_phone'] ?? '')),
        'notes' => trim((string)($source['notes'] ?? '')),
    ];
}

// When the user opens a guide/vehicle profile, save every value already entered.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['view_vehicle']) || isset($_POST['view_guide']))) {
    $draftStore->save(booking_input($_POST));

    if (isset($_POST['view_vehicle'])) {
        $id = (int)$_POST['view_vehicle'];
        header('Location: vehicle-profile.php?id=' . $id . '&return=booking');
    } else {
        $id = (int)$_POST['view_guide'];
        header('Location: guide-profile.php?id=' . $id . '&return=booking');
    }
    exit;
}

$resuming = isset($_GET['resume']) && $_GET['resume'] === '1';
$source = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $_POST
    : ($resuming ? $draftStore->get() : $_GET);
$data = booking_input($source);

$park = $data['park_id'];
$date = $data['date'];
$slot = $data['slot_id'];
$guests = $data['guests'];
$language = $data['preferred_language'];
$selectedVehicleId = $data['vehicle_id'];
$selectedGuideId = $data['guide_id'];
$pickupLocation = $data['pickup_location'];
$contactPhone = $data['contact_phone'];
$notes = $data['notes'];

// Prefill the safari-day phone number for a new booking if the account has one.
if ($contactPhone === '' && !$resuming && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $phoneStmt = $pdo->prepare('SELECT phone FROM users WHERE id=?');
    $phoneStmt->execute([$_SESSION['user_id']]);
    $contactPhone = (string)($phoneStmt->fetchColumn() ?: '');
}

$slots = $park ? $parksRepo->activeSlots($park) : [];
$selectedSlot = null;
foreach ($slots as $s) {
    if ((int)$s['id'] === $slot) {
        $selectedSlot = $s;
        break;
    }
}

$vehicles = [];
$guides = [];
$availabilityReady = $park
    && $selectedSlot
    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    && $date >= date('Y-m-d');

$slotCapacity = null;
$slotUsed = 0;
$slotRemaining = 0;
if ($availabilityReady) {
    $slotKey = BookingService::slotKey($selectedSlot);
    $vehicles = $vehiclesRepo->availableFor($park, $guests, $date, $slot, $slotKey);
    $guides = $guidesRepo->availableFor($park, $date, $slot, $slotKey, $language);
    $capStmt = $pdo->prepare("SELECT ps.vehicle_cap,(SELECT COUNT(*) FROM bookings b WHERE b.slot_id=ps.id AND b.entry_date=? AND b.status IN('pending','confirmed')) used FROM park_slots ps WHERE ps.id=? AND ps.park_id=?");
    $capStmt->execute([$date,$slot,$park]);
    $capRow = $capStmt->fetch();
    if ($capRow) {
        $slotCapacity = (int)$capRow['vehicle_cap'];
        $slotUsed = (int)$capRow['used'];
        $slotRemaining = max(0, $slotCapacity - $slotUsed);
    }
}

// If a saved selection is no longer available, remove only that selection.
$vehicleIds = array_map(static fn(array $v): int => (int)$v['id'], $vehicles);
$guideIds = array_map(static fn(array $g): int => (int)$g['user_id'], $guides);
if ($selectedVehicleId && !in_array($selectedVehicleId, $vehicleIds, true)) {
    $selectedVehicleId = 0;
}
if ($selectedGuideId && !in_array($selectedGuideId, $guideIds, true)) {
    $selectedGuideId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['vehicle_id'] = $selectedVehicleId;
    $data['guide_id'] = $selectedGuideId;

    $valid = $data['park_id']
        && $data['slot_id']
        && $data['vehicle_id']
        && $data['guide_id']
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])
        && $data['date'] >= date('Y-m-d')
        && $data['guests'] >= 1
        && $data['guests'] <= 12
        && validate_phone($data['contact_phone'], true)
        && strlen($data['pickup_location']) >= 2
        && strlen($data['pickup_location']) <= 180
        && strlen($data['notes']) <= 1000;

    if (!$valid) {
        flash('error', 'Please complete all required safari details. Use a valid Sri Lankan contact number, choose an available vehicle and guide, and check the date and guest count.');
    } else {
        try {
            $id = $bookingService->createSafari($data, (int)$_SESSION['user_id']);
            $draftStore->clear();
            flash('success', 'Available and reserved! Your verified guide and jeep are secured for this safari time. Complete PayHere payment to confirm your safari.');
            header('Location: payment.php?id=' . $id);
            exit;
        } catch (BookingException $e) {
            // Keep the submitted values on screen so the tourist can correct only what changed.
            flash('error', $e->getMessage());
        }
    }
}

dashboard_top('Book safari', 'Tourist portal');
back_button('../tourist/dashboard.php', 'Back to dashboard');
?>
<div class="eyebrow">Plan / compare / reserve</div>
<h1 class="dash-title">Build your safari.</h1>
<p class="section-copy">Choose your date and safari time. WildTrail checks availability immediately and shows only verified guides and jeeps that are free. No booking approval wait.</p>

<?php if ($resuming && $draftStore->get()): ?>
    <div class="note">Your unfinished booking was restored. You can continue where you left off.</div>
<?php endif; ?>

<form class="panel" method="get" id="availabilityForm">
    <div class="form-grid">
        <div class="field">
            <label for="park">National park</label>
            <select id="park" name="park" required>
                <option value="">Choose a park</option>
                <?php foreach ($parks as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $park === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> · entry LKR <?= number_format((float)$p['entry_fee'], 0) ?> / guest
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="date">Entry date</label>
            <input id="date" type="date" name="date" min="<?= date('Y-m-d') ?>" value="<?= e($date) ?>" required>
        </div>
    </div>

    <div class="form-grid">
        <div class="field">
            <label for="slot">Safari time</label>
            <select id="slot" name="slot" required <?= $park ? '' : 'disabled' ?>>
                <option value="">Choose safari time</option>
                <?php foreach ($slots as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= $slot === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['label']) ?> · <?= e(substr($s['start_time'], 0, 5)) ?>–<?= e(substr($s['end_time'], 0, 5)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!$park): ?><small>Select a park first to load safari times.</small><?php endif; ?>
        </div>
        <div class="field">
            <label for="guests">Guests</label>
            <input id="guests" type="number" name="guests" min="1" max="12" value="<?= (int)$guests ?>" required>
        </div>
    </div>

    <div class="field">
        <label for="language">Preferred guide language <span class="small">(optional)</span></label>
        <select id="language" name="language">
            <option value="">Any language</option>
            <?php foreach (['English', 'Sinhala', 'Tamil', 'German', 'French'] as $l): ?>
                <option value="<?= e($l) ?>" <?= $language === $l ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn">Check availability →</button>
</form>

<?php if ($availabilityReady): ?>
<div class="panel" style="margin-bottom:18px">
    <?php if ($slotRemaining > 0 && $vehicles && $guides): ?>
        <strong>✓ Available for <?= e($date) ?> · <?= e($selectedSlot['label']) ?></strong>
        <p class="small" style="margin-bottom:0"><?= $slotRemaining ?> safari-time space<?= $slotRemaining===1?'':'s' ?> remaining · <?= count($vehicles) ?> verified jeep<?= count($vehicles)===1?'':'s' ?> · <?= count($guides) ?> licensed guide<?= count($guides)===1?'':'s' ?> available. Select your team and continue directly to payment.</p>
    <?php else: ?>
        <strong>This safari time cannot be booked with your current choices.</strong>
        <p class="small" style="margin-bottom:0"><?php if ($slotRemaining <= 0): ?>The selected safari time is fully booked. <?php endif; ?>Try another safari time/date<?php if (!$guides && $language!==''): ?> or choose “Any language”<?php endif; ?>. WildTrail will immediately recheck availability.</p>
    <?php endif; ?>
</div>
<form method="post" id="reservationForm">
    <?= csrf_field() ?>
    <input type="hidden" name="park_id" value="<?= (int)$park ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">
    <input type="hidden" name="slot_id" value="<?= (int)$slot ?>">
    <input type="hidden" name="guests" value="<?= (int)$guests ?>">
    <input type="hidden" name="preferred_language" value="<?= e($language) ?>">

    <div class="selection-head">
        <div>
            <div class="eyebrow">Step 1 / vehicle</div>
            <h2>Choose your safari jeep.</h2>
        </div>
        <span><?= count($vehicles) ?> available</span>
    </div>

    <div class="choice-grid">
        <?php foreach ($vehicles as $v): ?>
            <div class="choice-card <?= $selectedVehicleId === (int)$v['id'] ? 'selected-choice' : '' ?>">
                <label class="choice-select">
                    <input type="radio" name="vehicle_id" value="<?= (int)$v['id'] ?>" <?= $selectedVehicleId === (int)$v['id'] ? 'checked' : '' ?> required>
                    <span>Select this jeep</span>
                </label>
                <div class="choice-media">
                    <?php if (!empty($v['photo'])): ?>
                        <img src="../uploads/photos/<?= e($v['photo']) ?>" alt="<?= e($v['make_model']) ?>">
                    <?php else: ?>
                        <div class="vehicle-art">4×4</div>
                    <?php endif; ?>
                </div>
                <div class="choice-body">
                    <div class="choice-top">
                        <strong><?= e($v['make_model']) ?></strong>
                        <span class="badge">verified</span>
                    </div>
                    <p><?= e($v['plate_number']) ?> · <?= (int)$v['capacity'] ?> seats · <?= e(ucfirst($v['safari_type'])) ?> safari</p>
                    <p class="small">Driver/operator: <?= e($v['driver_name']) ?> · ★ <?= number_format((float)$v['vehicle_rating'], 1) ?> (<?= (int)$v['review_count'] ?>)</p>
                    <div class="tags dark">
                        <?php foreach (array_filter(array_map('trim', explode(',', (string)($v['amenities'] ?? '')))) as $a): ?>
                            <span class="tag"><?= e($a) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="choice-price">LKR <?= number_format((float)$v['base_price'], 0) ?><small>per jeep</small></div>
                    <button type="submit" name="view_vehicle" value="<?= (int)$v['id'] ?>" class="text-link link-button" formnovalidate>View vehicle details →</button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$vehicles): ?>
            <div class="panel">
                <strong>No verified vehicle matches this date or group size.</strong>
                <p>Only jeeps approved for this park and explicitly available at this time are shown. Try another safari time or group size.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="selection-head">
        <div>
            <div class="eyebrow">Step 2 / guide</div>
            <h2>Choose who leads the experience.</h2>
        </div>
        <span><?= count($guides) ?> available</span>
    </div>

    <div class="choice-grid">
        <?php foreach ($guides as $g): ?>
            <div class="choice-card <?= $selectedGuideId === (int)$g['user_id'] ? 'selected-choice' : '' ?>">
                <label class="choice-select">
                    <input type="radio" name="guide_id" value="<?= (int)$g['user_id'] ?>" <?= $selectedGuideId === (int)$g['user_id'] ? 'checked' : '' ?> required>
                    <span>Select this guide</span>
                </label>
                <div class="guide-avatar">
                    <?php if (!empty($g['profile_photo'])): ?>
                        <img src="../uploads/photos/<?= e($g['profile_photo']) ?>" alt="<?= e($g['full_name']) ?>">
                    <?php else: ?>
                        <span><?= e(strtoupper(substr($g['full_name'], 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div class="choice-body">
                    <div class="choice-top">
                        <strong><?= e($g['full_name']) ?></strong>
                        <span class="badge">licensed</span>
                    </div>
                    <p>★ <?= number_format((float)$g['guide_rating'], 1) ?> (<?= (int)$g['review_count'] ?> reviews) · <?= (int)$g['experience_years'] ?> years</p>
                    <p class="small"><b>Languages:</b> <?= e($g['languages']) ?><br><b>Specialties:</b> <?= e($g['specialties']) ?></p>
                    <div class="choice-price">LKR <?= number_format((float)$g['guide_fee'], 0) ?><small>guide fee</small></div>
                    <button type="submit" name="view_guide" value="<?= (int)$g['user_id'] ?>" class="text-link link-button" formnovalidate>View full guide profile →</button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$guides): ?>
            <div class="panel">
                <strong>No guide is available for this filter.</strong>
                <p>Only guides approved for this park and who published availability for this exact date/time are shown. Try “Any language”, another safari time, or another date.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($vehicles && $guides): ?>
        <div class="panel">
            <div class="eyebrow">Step 3 / trip details</div>
            <h2>Where should your field team meet you?</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="pickup_location">Pickup / meeting point</label>
                    <input id="pickup_location" name="pickup_location" required minlength="2" maxlength="180"
                           value="<?= e($pickupLocation) ?>" placeholder="Hotel, park gate, or agreed meeting point">
                </div>
                <div class="field">
                    <label for="contact_phone">Contact number for safari day</label>
                    <input id="contact_phone" name="contact_phone" type="tel" required maxlength="18"
                           pattern="(?:07[0-9]{8}|\+[1-9][0-9]{6,14})" value="<?= e($contactPhone) ?>" placeholder="0771234567 or +447911123456">
                </div>
            </div>
            <div class="field">
                <label for="notes">Requests or accessibility notes</label>
                <textarea id="notes" name="notes" maxlength="1000" rows="3" placeholder="Child seat, mobility needs, photography interests, dietary notes for packed breakfast…"><?= e($notes) ?></textarea>
            </div>
            <div class="price-note"><strong>Final price is calculated from:</strong> park entry × guests + selected jeep + selected guide. You see the complete breakdown before paying.</div>
            <button class="btn orange">Reserve available team & pay →</button>
        </div>
    <?php endif; ?>
</form>
<?php endif; ?>

<script>
(() => {
    const form = document.getElementById('availabilityForm');
    const park = document.getElementById('park');
    if (!form || !park) return;

    park.addEventListener('change', () => {
        // Safari-time options belong to a park, so reload them while retaining safe filters.
        const params = new URLSearchParams(new FormData(form));
        params.delete('slot');
        window.location.href = 'book.php?' + params.toString();
    });

    document.querySelectorAll('#reservationForm input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.choice-card').forEach((card) => card.classList.remove('selected-choice'));
            document.querySelectorAll('#reservationForm input[type="radio"]:checked').forEach((checked) => {
                const card = checked.closest('.choice-card');
                if (card) card.classList.add('selected-choice');
            });
        });
    });
})();
</script>
<?php dashboard_bottom(); ?>
