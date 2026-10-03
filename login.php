<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';

use WildTrail\Repositories\UserRepository;
use WildTrail\Support\Validator;

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . home_for_role($_SESSION['user_role']));
    exit;
}

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!Validator::email($email)) {
        flash('error', 'Enter a valid email address.');
    } elseif ($password === '') {
        flash('error', 'Enter your password.');
    } else {
        $users = new UserRepository($pdo);
        $row = $users->findByEmail($email);
        $user = auth_service()->attempt($email, $password);

        if ($user) {
            header('Location: ' . $user->dashboardPath());
            exit;
        }

        flash(
            'error',
            $row && $row['status'] === 'pending'
                ? 'Your account is awaiting administrator approval.'
                : 'Incorrect email or password, or the account is inactive.'
        );
    }
}

page_top('Sign in');
?>
<div class="auth-wrap">
    <div class="auth-visual">
        <div>
            <div class="eyebrow">Return to your field notebook</div>
            <h1 class="display" style="font-size:66px">Welcome back to the <em>field.</em></h1>
        </div>
    </div>
    <div class="auth-panel">
        <form class="auth-card" method="post">
            <?= csrf_field() ?>
            <h1>Welcome back</h1>
            <p class="small">Sign in to continue to your role workspace.</p>
            <?php if (GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== ''): ?>
                <a class="oauth" href="auth/google-start.php"><span class="google-g">G</span> Continue with Google</a>
                <div class="divider">or</div>
            <?php endif; ?>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required maxlength="190"
                       autocomplete="email" value="<?= e($email) ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required maxlength="72"
                       autocomplete="current-password">
            </div>
            <button class="btn" style="width:100%">Continue →</button>
            <p class="small" style="margin-top:16px">No account? <a href="register.php"><strong>Create one</strong></a></p>
        </form>
    </div>
</div>
<?php page_bottom(); ?>
