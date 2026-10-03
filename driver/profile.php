<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('driver');
use WildTrail\Support\Validator;
$uid=(int)$_SESSION['user_id'];
$pdo->prepare("INSERT IGNORE INTO driver_profiles(user_id) VALUES(?)")->execute([$uid]);
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $photo=safe_upload('profile_photo',__DIR__.'/../uploads/photos',['image/jpeg','image/png']);
        $licDoc=safe_upload('license_doc',__DIR__.'/../uploads/docs');
        $phone=trim((string)($_POST['phone']??''));
        $license=trim((string)($_POST['license_no']??''));
        $languages=trim((string)($_POST['languages']??''));
        $bio=trim((string)($_POST['bio']??''));
        $years=max(0,min(60,(int)($_POST['experience_years']??0)));
        if($phone!=='' && !Validator::sriLankanPhone($phone, false)) throw new RuntimeException('Enter a valid phone number, for example 0771234567 or +94771234567.');
        if($license==='') throw new RuntimeException('Enter your driving licence number.');
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET phone=? WHERE id=?')->execute([$phone,$uid]);
        $pdo->prepare('UPDATE driver_profiles SET license_no=?,languages=?,bio=?,experience_years=?,profile_photo=COALESCE(?,profile_photo),license_doc=COALESCE(?,license_doc) WHERE user_id=?')->execute([$license,$languages,$bio,$years,$photo,$licDoc,$uid]);
        $pdo->commit();
        flash('success','Driver profile updated. Vehicle compliance and approval remain under Vehicle & documents.');
        header('Location: profile.php'); exit;
    }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); flash('error',$e->getMessage()); }
}
$st=$pdo->prepare('SELECT u.full_name,u.email,u.phone,d.* FROM users u LEFT JOIN driver_profiles d ON d.user_id=u.id WHERE u.id=?');$st->execute([$uid]);$d=$st->fetch();
dashboard_top('Driver profile','Driver workspace');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Driver profile.</h1>
<p class="section-copy">Keep your personal and professional details here. Vehicle details, operating parks and vehicle compliance documents stay in <strong>Vehicle & documents</strong>.</p>
<form class="panel" method="post" enctype="multipart/form-data">
<?=csrf_field()?>
<div class="form-grid"><div class="field"><label>Full name</label><input value="<?=e($d['full_name']??'')?>" disabled></div><div class="field"><label>Email</label><input value="<?=e($d['email']??'')?>" disabled></div></div>
<div class="form-grid"><div class="field"><label>Phone</label><input name="phone" value="<?=e($d['phone']??'')?>" placeholder="0771234567 or +94771234567"></div><div class="field"><label>Driving licence number</label><input name="license_no" required value="<?=e($d['license_no']??'')?>"></div></div>
<div class="form-grid"><div class="field"><label>Years of safari driving experience</label><input type="number" min="0" max="60" name="experience_years" value="<?=e($d['experience_years']??0)?>"></div><div class="field"><label>Languages</label><input name="languages" value="<?=e($d['languages']??'')?>" placeholder="Sinhala, English, Tamil"></div></div>
<div class="field"><label>About you</label><textarea name="bio" rows="5" placeholder="Tell travellers about your safari driving experience and approach."><?=e($d['bio']??'')?></textarea></div>
<div class="form-grid"><div class="field"><label>Profile photo</label><input type="file" name="profile_photo" accept=".jpg,.jpeg,.png"><?php if(!empty($d['profile_photo'])):?><small>Current photo saved.</small><?php endif;?></div><div class="field"><label>Driving licence document</label><input type="file" name="license_doc" accept=".pdf,.jpg,.jpeg,.png"><?php if(!empty($d['license_doc'])):?><small>Current licence document saved.</small><?php endif;?></div></div>
<button class="btn">Save driver profile</button>
</form>
<?php dashboard_bottom(); ?>
