<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';

use WildTrail\Repositories\UserRepository;
use WildTrail\Services\RegistrationService;
use WildTrail\Support\Validator;

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . home_for_role($_SESSION['user_role']));
    exit;
}

$name = '';
$email = '';
$phone = '';
$nic = '';
$role = 'tourist';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $role = $_POST['role'] ?? 'tourist';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (!Validator::name($name)) {
        $errors[] = 'Enter a valid full name using letters, spaces, apostrophes or hyphens.';
    }
    if (!Validator::email($email)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!Validator::sriLankanPhone($phone, true)) {
        $errors[] = 'Enter a valid phone number, for example 0771234567 or +447911123456.';
    }
    if (!Validator::nicOrPassport($nic, false)) {
        $errors[] = 'Enter a valid NIC or passport number.';
    }
    if (!in_array($role, ['tourist', 'driver', 'guide'], true)) {
        $errors[] = 'Select a valid account type.';
    }
    if (!Validator::password($password)) {
        $errors[] = 'Password must be 8–72 characters and include uppercase, lowercase, a number and a special character.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
    } else {
        try {
            $users = new UserRepository($pdo);
            if ($users->findByEmail($email)) {
                throw new RuntimeException('That email is already registered.');
            }

            $registration = new RegistrationService($pdo, $users);
            $result = $registration->register($name, $email, $phone, $password, $role, $nic);
            $status = $result['status'];

            flash(
                'success',
                $status === 'pending'
                    ? 'Account created. An administrator must approve your professional role.'
                    : 'Account created. You can sign in now.'
            );
            header('Location: login.php');
            exit;
        } catch (InvalidArgumentException|RuntimeException $e) {
            flash('error', $e->getMessage());
        } catch (PDOException $e) {
            flash('error', 'That email is already registered.');
        }
    }
}

page_top('Create account');
?>
<div class="auth-wrap">
    <div class="auth-visual">
        <div>
            <div class="eyebrow">Join the network</div>
            <h1 class="display" style="font-size:66px">Your next wild story starts <em>here.</em></h1>
        </div>
    </div>
    <div class="auth-panel">
        <form class="auth-card" method="post">
            <?= csrf_field() ?>
            <h1>Create account</h1>
            <p class="small">Tourists activate immediately. Driver and guide accounts require admin approval.</p>

            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" required minlength="2" maxlength="120"
                       autocomplete="name" value="<?= e($name) ?>">
                <small>Letters and spaces only, with apostrophe/hyphen if needed.</small>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required maxlength="190"
                       autocomplete="email" value="<?= e($email) ?>">
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="phone">Phone number</label>
                    <input id="phone" name="phone" type="tel" required maxlength="18"
                           autocomplete="tel" value="<?= e($phone) ?>"
                           pattern="(?:07[0-9]{8}|\+[1-9][0-9]{6,14})"
                           placeholder="0771234567">
                    <small>Local example: 0771234567 · International example: +447911123456.</small>
                </div>
                <div class="field">
                    <label for="nic">NIC / passport <span class="small">(optional)</span></label>
                    <input id="nic" name="nic" maxlength="20" value="<?= e($nic) ?>"
                           placeholder="200012345678">
                </div>
            </div>

            <div class="field">
                <label for="role">Account type</label>
                <select id="role" name="role" required>
                    <option value="tourist" <?= $role === 'tourist' ? 'selected' : '' ?>>Tourist</option>
                    <option value="driver" <?= $role === 'driver' ? 'selected' : '' ?>>Driver / jeep owner</option>
                    <option value="guide" <?= $role === 'guide' ? 'selected' : '' ?>>Licensed wildlife guide</option>
                </select>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required minlength="8" maxlength="72"
                       autocomplete="new-password"
                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,72}">
                <small>8+ characters with uppercase, lowercase, number and special character.</small>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" name="confirm_password" type="password" required minlength="8" maxlength="72"
                       autocomplete="new-password">
                <small id="passwordMatch" aria-live="polite"></small>
            </div>

            <button class="btn" style="width:100%">Create account</button>
            <p class="small" style="margin-top:16px">Already registered? <a href="login.php"><strong>Sign in</strong></a></p>
        </form>
    </div>
</div>
<script>
(() => {
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const message = document.getElementById('passwordMatch');

    function checkMatch() {
        if (!confirm.value) {
            confirm.setCustomValidity('');
            message.textContent = '';
            return;
        }
        const matches = password.value === confirm.value;
        confirm.setCustomValidity(matches ? '' : 'Passwords do not match.');
        message.textContent = matches ? 'Passwords match ✓' : 'Passwords do not match.';
    }

    password.addEventListener('input', checkMatch);
    confirm.addEventListener('input', checkMatch);
})();
</script>
<?php page_bottom(); ?>
