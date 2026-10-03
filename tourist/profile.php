<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');
if($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim($_POST['name']);
    $phone=trim($_POST['phone']);
    if($name&&validate_phone($phone)) {
        $pdo->prepare('UPDATE users SET full_name=?,phone=? WHERE id=?')->execute([$name,$phone,$_SESSION['user_id']]);
        $_SESSION['user_name']=$name;
        flash('success','Profile updated.');
        header('Location: profile.php');
        exit;
    }
}
$st=$pdo->prepare('SELECT * FROM users WHERE id=?');
$st->execute([$_SESSION['user_id']]);
$u=$st->fetch();
dashboard_top('Profile','Tourist portal');
back_button('../tourist/dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Your profile.</h1>
<form class="panel" method="post">
<?=
csrf_field()
?>
<div class="field">
    <label>Name</label>
    <input name="name" value="<?=e($u['full_name'])?>">
</div>
<div class="field">
    <label>Email</label>
    <input value="<?=e($u['email'])?>" disabled>
</div>
<div class="field">
    <label>Phone</label>
    <input name="phone" value="<?=e($u['phone'])?>">
</div>
<button class="btn">Save changes</button>
</form>
<?php
dashboard_bottom();
?>
