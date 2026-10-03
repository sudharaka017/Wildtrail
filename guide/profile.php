<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('guide');
$uid=$_SESSION['user_id'];
$parks=$pdo->query("SELECT id,name FROM parks WHERE status='active' ORDER BY name")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $currentSt=$pdo->prepare('SELECT * FROM guide_profiles WHERE user_id=?'); $currentSt->execute([$uid]); $current=$currentSt->fetch() ?: [];
        $curP=$pdo->prepare('SELECT park_id FROM guide_parks WHERE guide_id=? ORDER BY park_id'); $curP->execute([$uid]); $currentParks=array_map('intval',$curP->fetchAll(PDO::FETCH_COLUMN));
        $doc=safe_upload('license_doc',__DIR__.'/../uploads/docs');
        $photo=safe_upload('profile_photo',__DIR__.'/../uploads/photos',['image/jpeg','image/png']);
        $license=trim((string)($_POST['license_no']??''));
        $bio=trim((string)($_POST['bio']??'')); $languages=trim((string)($_POST['languages']??'')); $specialties=trim((string)($_POST['specialties']??''));
        $years=max(0,min(50,(int)($_POST['experience_years']??0))); $fee=max(0,(float)($_POST['guide_fee']??0));
        if($license==='') throw new RuntimeException('Enter your guide license number.');
        $parkIds=array_values(array_unique(array_filter(array_map('intval', $_POST['parks']??[])))); sort($parkIds); sort($currentParks);
        if(!$parkIds) throw new RuntimeException('Select at least one national park you are authorised to guide in.');
        $needsReverify=($license !== (string)($current['license_no']??'')) || $doc!==null || $parkIds!==$currentParks;
        $verified=$needsReverify ? 0 : (int)($current['verified']??0);
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE guide_profiles SET license_no=?,bio=?,languages=?,specialties=?,experience_years=?,guide_fee=?,license_doc=COALESCE(?,license_doc),profile_photo=COALESCE(?,profile_photo),verified=? WHERE user_id=?')->execute([$license,$bio,$languages,$specialties,$years,$fee,$doc,$photo,$verified,$uid]);
        $pdo->prepare('DELETE FROM guide_parks WHERE guide_id=?')->execute([$uid]);
        $insPark=$pdo->prepare('INSERT INTO guide_parks(guide_id,park_id) VALUES(?,?)'); foreach($parkIds as $pid) $insPark->execute([$uid,$pid]);
        if($needsReverify){
            $admins=$pdo->query("SELECT id FROM users WHERE role IN ('admin','superadmin') AND status='active'")->fetchAll(PDO::FETCH_COLUMN);
            $notice=$pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)');
            foreach($admins as $adminId) $notice->execute([(int)$adminId,'Guide '.($_SESSION['user_name']??('user #'.$uid)).' submitted licence/park details for verification.']);
        }
        $pdo->commit();
        flash('success',$needsReverify?'License/park details changed. Profile saved and sent for administrator re-verification.':'Profile updated. Your existing verification remains active.');
        header('Location: profile.php'); exit;
    } catch(Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); flash('error',$e->getMessage()); }
}
$st=$pdo->prepare('SELECT * FROM guide_profiles WHERE user_id=?');
$st->execute([$uid]);
$g=$st->fetch();
$gp=$pdo->prepare('SELECT park_id FROM guide_parks WHERE guide_id=?'); $gp->execute([$uid]);
$selectedParks=array_map('intval',$gp->fetchAll(PDO::FETCH_COLUMN));
dashboard_top('Guide profile','Guide workspace');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Licensed to lead.</h1>
<p class="section-copy">Tourists use this profile to compare guides before booking, so keep languages, experience and specialties accurate.</p>
<form class="panel" method="post" enctype="multipart/form-data">
<?=
csrf_field()
?>
<div class="form-grid">
    <div class="field">
        <label>License number</label>
        <input name="license_no" required value="<?=e($g['license_no']??'')?>">
</div>
<div class="field">
    <label>Years of field experience</label>
    <input type="number" name="experience_years" min="0" max="50" value="<?=e($g['experience_years']??0)?>">
</div>
</div>

<div class="field">
    <label>National parks you guide in</label>
    <div class="panel" style="padding:14px">
    <?php foreach($parks as $park): ?>
        <label style="display:inline-flex;align-items:center;gap:7px;margin:6px 18px 6px 0">
            <input type="checkbox" name="parks[]" value="<?= (int)$park['id'] ?>" <?= in_array((int)$park['id'],$selectedParks,true)?'checked':'' ?>>
            <?= e($park['name']) ?>
        </label>
    <?php endforeach; ?>
    </div>
    <small>Select only parks where you are licensed/authorised to operate. Changing parks sends the profile for administrator re-verification.</small>
</div>
<div class="field">
    <label>Languages</label>
    <input name="languages" value="<?=e($g['languages']??'')?>" placeholder="Sinhala, English, German">
</div>
<div class="field">
    <label>Specialties</label>
    <input name="specialties" value="<?=e($g['specialties']??'')?>" placeholder="Leopard tracking, birding, photography">
</div>
<div class="field">
    <label>Guide fee per safari (LKR)</label>
    <input type="number" step="100" min="0" name="guide_fee" value="<?=e($g['guide_fee']??6000)?>">
</div>
<div class="field">
    <label>Bio</label>
    <textarea name="bio" rows="5"><?=e($g['bio']??'')?></textarea>
</div>
<div class="form-grid">
    <div class="field">
        <label>Profile photo</label>
        <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
    </div>
    <div class="field">
        <label>License document</label>
        <input type="file" name="license_doc" accept=".pdf,.jpg,.jpeg,.png">
    </div>
</div>
<p>Status: <span class="badge">
<?=
!empty($g['verified'])?'verified':'pending verification'
?>
</span>
</p>
<button class="btn">Save & submit for verification</button>
</form>
<?php
dashboard_bottom();
?>
