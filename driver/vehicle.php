<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('driver');
use WildTrail\Support\Validator;
$uid=$_SESSION['user_id'];
$parks=$pdo->query("SELECT id,name FROM parks WHERE status='active' ORDER BY name")->fetchAll();
$formInput=null;
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $reg=safe_upload('registration_doc',__DIR__.'/../uploads/docs');
        $ins=safe_upload('insurance_doc',__DIR__.'/../uploads/docs');
        $fit=safe_upload('fitness_doc',__DIR__.'/../uploads/docs');
        $photo=safe_upload('photo',__DIR__.'/../uploads/photos',['image/jpeg','image/png']);
        $st=$pdo->prepare('SELECT * FROM vehicles WHERE owner_id=? LIMIT 1');
        $st->execute([$uid]);
        $v=$st->fetch();
$selectedParks=[];
if($v){ $vp=$pdo->prepare('SELECT park_id FROM vehicle_parks WHERE vehicle_id=?'); $vp->execute([(int)$v['id']]); $selectedParks=array_map('intval',$vp->fetchAll(PDO::FETCH_COLUMN)); }
        $currentParks=$selectedParks;
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['parks'])) $selectedParks=array_map('intval',(array)$_POST['parks']);
        $plate=trim($_POST['plate']??'');
        $model=trim($_POST['model']??'');
        $capacity=(int)($_POST['capacity']??0);
        $safariType=strtolower(trim($_POST['safari_type']??''));
        $amenities=trim($_POST['amenities']??'');
        $basePrice=(float)($_POST['base_price']??0);
        $regno=trim($_POST['regno']??'');
        $insno=trim($_POST['insno']??'');
        $fitno=trim($_POST['fitno']??'');
        $parkIds=array_values(array_unique(array_filter(array_map('intval', $_POST['parks']??[]))));
        if(!$parkIds) throw new RuntimeException('Select at least one national park this vehicle operates in.');
        $formInput=['plate_number'=>$plate,'make_model'=>$model,'capacity'=>$capacity,'safari_type'=>$safariType,'amenities'=>$amenities,'base_price'=>$basePrice,'registration_no'=>$regno,'insurance_no'=>$insno,'fitness_no'=>$fitno];

        if(!Validator::vehiclePlate($plate)) throw new RuntimeException('Enter a valid vehicle plate number.');
        if(!Validator::plainText($model,120,true)) throw new RuntimeException('Enter a valid make / model.');
        if($capacity<1 || $capacity>12) throw new RuntimeException('Passenger capacity must be between 1 and 12.');
        if(!in_array($safariType,['open','covered','premium'],true)) throw new RuntimeException('Select a valid safari type.');
        if($basePrice<0 || $basePrice>1000000) throw new RuntimeException('Enter a valid safari price.');
        if(!Validator::plainText($amenities,500,false)) throw new RuntimeException('Amenities must be 500 characters or fewer.');
        foreach([$regno,$insno,$fitno] as $documentNumber) {
            if(!Validator::plainText($documentNumber,100,false)) throw new RuntimeException('Document numbers must be 100 characters or fewer.');
        }

        $data=[$plate,$model,$capacity,$safariType,$amenities,$basePrice,$regno,$insno,$fitno];
        sort($parkIds); sort($currentParks);
        $needsReverify=!$v || $plate!==(string)($v['plate_number']??'') || $model!==(string)($v['make_model']??'') || $capacity!==(int)($v['capacity']??0) || $safariType!==(string)($v['safari_type']??'') || $regno!==(string)($v['registration_no']??'') || $insno!==(string)($v['insurance_no']??'') || $fitno!==(string)($v['fitness_no']??'') || $reg!==null || $ins!==null || $fit!==null || $parkIds!==$currentParks;
        $approval=$needsReverify?'pending':(string)($v['approval_status']??'pending');
        $pdo->beginTransaction();
        if($v) {
            $pdo->prepare("UPDATE vehicles SET plate_number=?,make_model=?,capacity=?,safari_type=?,amenities=?,base_price=?,registration_no=?,insurance_no=?,fitness_no=?,registration_doc=COALESCE(?,registration_doc),insurance_doc=COALESCE(?,insurance_doc),fitness_doc=COALESCE(?,fitness_doc),photo=COALESCE(?,photo),approval_status=? WHERE owner_id=?")->execute([...$data,$reg,$ins,$fit,$photo,$approval,$uid]);
        }
        else {
            $pdo->prepare('INSERT INTO vehicles(owner_id,plate_number,make_model,capacity,safari_type,amenities,base_price,registration_no,insurance_no,fitness_no,registration_doc,insurance_doc,fitness_doc,photo) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$uid,...$data,$reg,$ins,$fit,$photo]);
        }
        $vehicleId=$v ? (int)$v['id'] : (int)$pdo->lastInsertId();
        $pdo->prepare('DELETE FROM vehicle_parks WHERE vehicle_id=?')->execute([$vehicleId]);
        $insPark=$pdo->prepare('INSERT INTO vehicle_parks(vehicle_id,park_id) VALUES(?,?)');
        foreach($parkIds as $pid) $insPark->execute([$vehicleId,$pid]);
        if($needsReverify){
            $admins=$pdo->query("SELECT id FROM users WHERE role IN ('admin','superadmin') AND status='active'")->fetchAll(PDO::FETCH_COLUMN);
            $notice=$pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)');
            foreach($admins as $adminId) $notice->execute([(int)$adminId,'A driver submitted vehicle '.($plate?:'#'.$vehicleId).' for verification.']);
        }
        $pdo->commit();
        flash('success',$needsReverify?'Vehicle details submitted. Verification is Pending. An administrator must review and approve it.':'Vehicle profile updated. Existing approval remains active.');
        header('Location: vehicle.php');
        exit;
    }
    catch(Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        flash('error',$e->getMessage());
    }
}
$st=$pdo->prepare('SELECT * FROM vehicles WHERE owner_id=? LIMIT 1');
$st->execute([$uid]);
$v=$st->fetch();
$selectedParks=[];
if($v){ $vp=$pdo->prepare('SELECT park_id FROM vehicle_parks WHERE vehicle_id=?'); $vp->execute([(int)$v['id']]); $selectedParks=array_map('intval',$vp->fetchAll(PDO::FETCH_COLUMN)); }
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['parks'])) $selectedParks=array_map('intval',(array)$_POST['parks']);
if($formInput!==null) $v=array_merge($v?:[], $formInput);
dashboard_top('Vehicle & documents','Driver workspace');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Your safari vehicle.</h1>
<p class="section-copy">Tourists compare these details before booking. Photos and amenities should match the actual vehicle.</p>
<form class="panel" method="post" enctype="multipart/form-data">
<?=
csrf_field()
?>
<div class="form-grid">
    <div class="field">
        <label>Plate number</label>
        <input name="plate" required minlength="4" maxlength="15" pattern="[A-Za-z0-9 -]{4,15}" value="<?=e($v['plate_number']??'')?>">
</div>
<div class="field">
    <label>Make / model</label>
    <input name="model" required maxlength="120" value="<?=e($v['make_model']??'')?>">
</div>
</div>
<div class="form-grid">
    <div class="field">
        <label>Passenger capacity</label>
        <input type="number" name="capacity" min="1" max="12" value="<?=e($v['capacity']??6)?>">
</div>
<div class="field">
    <label>Safari type</label>
    <select name="safari_type" required>
<?php
foreach(['open','covered','premium'] as $x):
?>
<option value="<?=e($x)?>" <?= $x===strtolower((string)($v['safari_type']??'open'))?'selected':'' ?>><?=e(ucfirst($x).' safari jeep')?></option>
<?php
endforeach
?>
</select>
</div>
</div>

<div class="field">
    <label>National parks this jeep operates in</label>
    <div class="panel" style="padding:14px">
    <?php foreach($parks as $park): ?>
        <label style="display:inline-flex;align-items:center;gap:7px;margin:6px 18px 6px 0">
            <input type="checkbox" name="parks[]" value="<?= (int)$park['id'] ?>" <?= in_array((int)$park['id'],$selectedParks,true)?'checked':'' ?>>
            <?= e($park['name']) ?>
        </label>
    <?php endforeach; ?>
    </div>
    <small>Select only parks where this vehicle/operator is authorised to provide safaris. Changing parks requires administrator re-verification.</small>
</div>
<div class="form-grid">
    <div class="field">
        <label>Jeep price per safari (LKR)</label>
        <input type="number" min="0" step="100" name="base_price" value="<?=e($v['base_price']??18000)?>">
</div>
<div class="field">
    <label>Vehicle photo</label>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png">
</div>
</div>
<div class="field">
    <label>Amenities</label>
    <input name="amenities" maxlength="500" value="<?=e($v['amenities']??'')?>" placeholder="Raised seats, canopy, first-aid kit, USB charging">
</div>
<div class="form-grid">
    <div class="field">
        <label>Registration number</label>
        <input name="regno" maxlength="100" value="<?=e($v['registration_no']??'')?>">
<input type="file" name="registration_doc" accept=".pdf,.jpg,.jpeg,.png">
</div>
<div class="field">
    <label>Insurance number</label>
    <input name="insno" maxlength="100" value="<?=e($v['insurance_no']??'')?>">
<input type="file" name="insurance_doc" accept=".pdf,.jpg,.jpeg,.png">
</div>
</div>
<div class="field">
    <label>Fitness certificate</label>
    <input name="fitno" maxlength="100" value="<?=e($v['fitness_no']??'')?>">
<input type="file" name="fitness_doc" accept=".pdf,.jpg,.jpeg,.png">
</div>
<p><strong>Administrator verification:</strong> <span class="badge
<?=
e($v['approval_status']??'pending')
?>
">
<?=
e($v['approval_status']??'not submitted')
?>
</span>
</p>
<p class="small">You cannot verify your own vehicle. Save it here; an administrator reviews the details/documents and approves or rejects it.</p>
<button class="btn">Save & submit for admin verification</button>
</form>
<?php
dashboard_bottom();
?>
