<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT b.*,p.name,ps.label,u.full_name,u.email,u.phone,v.make_model,v.plate_number,du.full_name driver_name,gu.full_name guide_name FROM bookings b JOIN parks p ON p.id=b.park_id JOIN park_slots ps ON ps.id=b.slot_id JOIN users u ON u.id=b.visitor_id LEFT JOIN vehicles v ON v.id=b.vehicle_id LEFT JOIN users du ON du.id=b.driver_id LEFT JOIN users gu ON gu.id=b.guide_id WHERE b.id=? AND b.visitor_id=?");
$st->execute([$id, $_SESSION['user_id']]);
$b = $st->fetch();

if (!$b) {
    flash('error', 'Booking not found.');
    header('Location: bookings.php');
    exit;
}
if ($b['status'] === 'cancelled') {
    flash('error', 'A cancelled booking cannot be paid.');
    header('Location: bookings.php');
    exit;
}
if ($b['payment_status'] === 'paid') {
    header('Location: booking-success.php?id='.$id);
    exit;
}

$demoErrors = [];
$demoCardholder = '';
$demoCard = '';
$demoExpiry = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && PAYHERE_MODE === 'local_demo') {
    $demoCardholder = trim((string)($_POST['cardholder'] ?? ''));
    $demoCard = preg_replace('/\D+/', '', (string)($_POST['card_number'] ?? ''));
    $demoExpiry = trim((string)($_POST['expiry'] ?? ''));
    $demoCvv = preg_replace('/\D+/', '', (string)($_POST['cvv'] ?? ''));

    if (mb_strlen($demoCardholder) < 2 || mb_strlen($demoCardholder) > 80) {
        $demoErrors[] = 'Enter the demo cardholder name.';
    }
    if ($demoCard !== '4916217501611292') {
        $demoErrors[] = 'Use the test card number 4916 2175 0161 1292.';
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $demoExpiry, $match)) {
        $demoErrors[] = 'Enter expiry as MM/YY.';
    } else {
        $month = (int)$match[1];
        $year = 2000 + (int)$match[2];
        $expiryEnd = strtotime(sprintf('%04d-%02d-01 +1 month', $year, $month));
        if ($expiryEnd === false || $expiryEnd <= time()) {
            $demoErrors[] = 'Use a future expiry date.';
        }
    }
    if ($demoCvv !== '123') {
        $demoErrors[] = 'Use the demo CVV 123.';
    }

    if (!$demoErrors) {
        $pdo->beginTransaction();
        $lock = $pdo->prepare("SELECT payment_status,status FROM bookings WHERE id=? AND visitor_id=? FOR UPDATE");
        $lock->execute([$id, $_SESSION['user_id']]);
        $row = $lock->fetch();

        if (!$row || $row['status'] === 'cancelled') {
            $pdo->rollBack();
            flash('error', 'This booking is no longer available for payment.');
            header('Location: bookings.php');
            exit;
        }

        if ($row['payment_status'] !== 'paid') {
            // Never store demo card details. Only the generated payment reference is saved.
            $reference = 'PAYHERE-DEMO-'.date('YmdHis').'-'.random_int(100, 999);
            $pdo->prepare("UPDATE bookings SET payment_status='paid',status='confirmed',payment_ref=?,reserved_until=NULL WHERE id=? AND visitor_id=?")
                ->execute([$reference, $id, $_SESSION['user_id']]);
            $pdo->prepare('INSERT INTO notifications(user_id,message) SELECT visitor_id,CONCAT("Payment successful. Safari ",booking_code," is confirmed and your QR permit is ready.") FROM bookings WHERE id=?')->execute([$id]);
            $team=$pdo->prepare('SELECT booking_code,guide_id,driver_id FROM bookings WHERE id=?'); $team->execute([$id]); $t=$team->fetch();
            if($t){ $n=$pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)'); if($t['guide_id'])$n->execute([$t['guide_id'],"Safari {$t['booking_code']} is now paid and confirmed."]); if($t['driver_id'])$n->execute([$t['driver_id'],"Safari {$t['booking_code']} is now paid and confirmed."]); }
        }
        $pdo->commit();

        flash('success', 'Payment completed successfully. Your safari is confirmed and your QR e-permit is ready.');
        header('Location: booking-success.php?id='.$id);
        exit;
    }
}

$configured = PAYHERE_MERCHANT_ID !== '' && PAYHERE_MERCHANT_SECRET !== '';
$action = PAYHERE_MODE === 'live' ? 'https://www.payhere.lk/pay/checkout' : 'https://sandbox.payhere.lk/pay/checkout';
$amount = number_format((float)$b['amount'], 2, '.', '');
$hash = $configured ? strtoupper(md5(PAYHERE_MERCHANT_ID.$b['booking_code'].$amount.PAYHERE_CURRENCY.strtoupper(md5(PAYHERE_MERCHANT_SECRET)))) : '';
$nameParts = preg_split('/\s+/', trim($b['full_name']), 2);
$first = $nameParts[0] ?? 'Visitor';
$last = $nameParts[1] ?? '-';

dashboard_top('Payment', 'Tourist portal');
back_button('bookings.php', 'Back to my bookings');
?>
<h1 class="dash-title">Secure checkout.</h1>
<div class="grid-2 payment-layout">
    <div class="panel">
        <div class="eyebrow">Booking <?= e($b['booking_code']) ?></div>
        <h2><?= e($b['name']) ?></h2>
        <p><?= e($b['entry_date']) ?> · <?= e($b['label']) ?> · <?= e($b['guests']) ?> guests</p>

        <div class="summary-lines">
            <span>Park entry</span><strong>LKR <?= number_format($b['entry_fee_component'], 0) ?></strong>
            <span>Safari jeep · <?= e($b['make_model']) ?></span><strong>LKR <?= number_format($b['vehicle_fee_component'], 0) ?></strong>
            <span>Guide · <?= e($b['guide_name']) ?></span><strong>LKR <?= number_format($b['guide_fee_component'], 0) ?></strong>
        </div>
        <h2>Total LKR <?= number_format($b['amount'], 2) ?></h2>
        <p class="small">Driver/operator: <?= e($b['driver_name']) ?> · Pickup: <?= e($b['pickup_location']) ?></p>
    </div>

    <div class="panel payhere-checkout">
        <div class="payment-brand-row">
            <div>
                <div class="eyebrow">Payment gateway</div>
                <h2 class="payment-brand">PayHere</h2>
            </div>
            <span class="badge">Secure checkout</span>
        </div>

        <div class="note" style="margin-bottom:16px">
            <strong>★ Cancellation &amp; Refund Policy</strong><br>
            If you cancel an eligible confirmed safari before the visit date, <strong><?= CANCELLATION_REFUND_PERCENT ?>% of the amount paid will be refunded</strong>.
            The remaining <?= 100-CANCELLATION_REFUND_PERCENT ?>% is retained as the cancellation charge.<br>
            <span class="small">★ Please review your safari date, safari time, guide and vehicle before making payment. Refunds are processed after cancellation and may require staff/payment-provider processing.</span>
        </div>

        <?php if (PAYHERE_MODE === 'local_demo'): ?>
            <div class="note"><strong>PayHere demo simulation.</strong> No real payment is made. Do not enter a real card number; the test card data below is validated but never stored.</div>

            <?php if ($demoErrors): ?>
                <div class="payment-errors" role="alert">
                    <strong>Please correct:</strong>
                    <ul><?php foreach ($demoErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" id="demo-payment-form" class="demo-card-form" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="demo-card-preview" aria-hidden="true">
                    <div class="demo-card-top"><span>PayHere</span><span>DEMO</span></div>
                    <div class="demo-card-number" id="card-preview-number">4916 2175 0161 1292</div>
                    <div class="demo-card-bottom"><span id="card-preview-name">TEST VISITOR</span><span id="card-preview-expiry">12/30</span></div>
                </div>

                <div class="field">
                    <label for="cardholder">Cardholder name</label>
                    <input id="cardholder" name="cardholder" maxlength="80" required value="<?= e($demoCardholder) ?>" placeholder="Test Visitor">
                </div>
                <div class="field">
                    <label for="card_number">Card number</label>
                    <input id="card_number" name="card_number" inputmode="numeric" maxlength="19" required value="<?= e($demoCard ? trim(chunk_split($demoCard, 4, ' ')) : '') ?>" placeholder="4916 2175 0161 1292">
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="expiry">Expiry</label>
                        <input id="expiry" name="expiry" maxlength="5" required value="<?= e($demoExpiry) ?>" placeholder="12/30">
                    </div>
                    <div class="field">
                        <label for="cvv">CVV</label>
                        <input id="cvv" name="cvv" type="password" inputmode="numeric" maxlength="3" required placeholder="123">
                    </div>
                </div>

                <div class="demo-credentials">
                    <strong>Demo card:</strong> 4916 2175 0161 1292 · future MM/YY · CVV 123
                </div>
                <button class="btn payment-submit" type="submit">Pay LKR <?= number_format($b['amount'], 2) ?> with PayHere Demo</button>
            </form>

            <script>
            (() => {
                const form = document.getElementById('demo-payment-form');
                if (!form) return;
                const card = document.getElementById('card_number');
                const name = document.getElementById('cardholder');
                const expiry = document.getElementById('expiry');
                const cvv = document.getElementById('cvv');
                const previewNumber = document.getElementById('card-preview-number');
                const previewName = document.getElementById('card-preview-name');
                const previewExpiry = document.getElementById('card-preview-expiry');

                const formatCard = value => value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
                card.addEventListener('input', () => {
                    card.value = formatCard(card.value);
                    previewNumber.textContent = card.value || '4916 2175 0161 1292';
                });
                name.addEventListener('input', () => previewName.textContent = (name.value || 'TEST VISITOR').toUpperCase().slice(0, 24));
                expiry.addEventListener('input', () => {
                    let v = expiry.value.replace(/\D/g, '').slice(0, 4);
                    if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
                    expiry.value = v;
                    previewExpiry.textContent = v || '12/30';
                });
                cvv.addEventListener('input', () => cvv.value = cvv.value.replace(/\D/g, '').slice(0, 3));
            })();
            </script>

        <?php elseif (!$configured): ?>
            <div class="note">PayHere is selected, but merchant credentials are not configured. Add your PayHere Sandbox Merchant ID and Merchant Secret in <code>includes/config.local.php</code>.</div>
        <?php else: ?>
            <div class="note">You will continue to the official PayHere <?= PAYHERE_MODE === 'sandbox' ? 'Sandbox' : 'payment gateway' ?> to complete checkout.</div>
            <form method="post" action="<?= e($action) ?>" style="margin-top:16px">
                <input type="hidden" name="merchant_id" value="<?= e(PAYHERE_MERCHANT_ID) ?>">
                <input type="hidden" name="return_url" value="<?= e(APP_URL.'/tourist/payment_return.php?id='.$id) ?>">
                <input type="hidden" name="cancel_url" value="<?= e(APP_URL.'/tourist/payment.php?id='.$id) ?>">
                <input type="hidden" name="notify_url" value="<?= e(APP_URL.'/tourist/payment_notify.php') ?>">
                <input type="hidden" name="order_id" value="<?= e($b['booking_code']) ?>">
                <input type="hidden" name="items" value="<?= e('Safari booking - '.$b['name']) ?>">
                <input type="hidden" name="currency" value="<?= e(PAYHERE_CURRENCY) ?>">
                <input type="hidden" name="amount" value="<?= e($amount) ?>">
                <input type="hidden" name="hash" value="<?= e($hash) ?>">
                <input type="hidden" name="first_name" value="<?= e($first) ?>">
                <input type="hidden" name="last_name" value="<?= e($last) ?>">
                <input type="hidden" name="email" value="<?= e($b['email']) ?>">
                <input type="hidden" name="phone" value="<?= e($b['phone'] ?: '0770000000') ?>">
                <input type="hidden" name="address" value="Sri Lanka">
                <input type="hidden" name="city" value="Colombo">
                <input type="hidden" name="country" value="Sri Lanka">
                <button class="btn payment-submit" type="submit">Continue to PayHere <?= PAYHERE_MODE === 'sandbox' ? 'Sandbox' : '' ?></button>
            </form>
            <?php if (PAYHERE_MODE === 'sandbox'): ?>
                <p class="small" style="margin-top:14px">For a complete localhost sandbox callback, expose the site through a public HTTPS URL/tunnel and set APP_URL to that address.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($b['reserved_until'])): ?>
<script>
(()=>{const end=new Date('<?= e(date('c', strtotime($b['reserved_until']))) ?>').getTime(); const btn=document.querySelector('.payment-submit'); const box=document.createElement('div'); box.className='note'; box.style.marginTop='12px'; document.querySelector('.payhere-checkout')?.prepend(box); function tick(){const left=end-Date.now(); if(left<=0){box.innerHTML='<strong>Reservation expired.</strong> The guide and jeep have been released. Return to booking to choose availability again.'; if(btn)btn.disabled=true; return;} const m=Math.floor(left/60000),sec=Math.floor((left%60000)/1000); box.innerHTML='<strong>Reservation held:</strong> '+m+':'+String(sec).padStart(2,'0')+' remaining to complete payment.'; setTimeout(tick,1000)} tick();})();
</script>
<?php endif; dashboard_bottom(); ?>
