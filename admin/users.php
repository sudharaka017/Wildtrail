<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
if(isset($_POST['status'],$_POST['id'])) {
    $s=$_POST['status'];
    if(in_array($s,['active','rejected','suspended'],true))$pdo->prepare('UPDATE users SET status=? WHERE id=? AND role NOT IN(\'admin\',\'superadmin\')')->execute([$s,(int)$_POST['id']]);
    header('Location: users.php');
    exit;
}
if(isset($_POST['vehicle'],$_POST['vstatus'])) {
    $vehicleId=(int)$_POST['vehicle']; $vstatus=(string)$_POST['vstatus'];
    if(in_array($vstatus,['approved','rejected'],true)){
        $pdo->prepare('UPDATE vehicles SET approval_status=? WHERE id=?')->execute([$vstatus,$vehicleId]);
        $o=$pdo->prepare('SELECT owner_id,plate_number FROM vehicles WHERE id=?'); $o->execute([$vehicleId]); $vr=$o->fetch();
        if($vr){
            if($vstatus==='approved') $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND role='driver' AND status='pending'")->execute([$vr['owner_id']]);
            $pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)')->execute([$vr['owner_id'], $vstatus==='approved' ? "Vehicle {$vr['plate_number']} was approved." : "Vehicle {$vr['plate_number']} was rejected. Please review your details/documents and resubmit."]);
        }
    }
    header('Location: users.php'); exit;
}
if(isset($_POST['guide'],$_POST['gstatus'])) {
    $guideId=(int)$_POST['guide']; $gstatus=(string)$_POST['gstatus'];
    if(in_array($gstatus,['approved','rejected'],true)){
        $pdo->prepare('UPDATE guide_profiles SET verified=? WHERE user_id=?')->execute([$gstatus==='approved'?1:0,$guideId]);
        if($gstatus==='approved') $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND role='guide' AND status='pending'")->execute([$guideId]);
        $pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)')->execute([$guideId,$gstatus==='approved'?'Your guide profile was verified. Publish availability to appear in tourist search.':'Your guide verification was not approved. Please review your licence/park details and resubmit.']);
    }
    header('Location: users.php'); exit;
}
$users=$pdo->query("SELECT * FROM users ORDER BY FIELD(status,'pending','active','suspended','rejected'),created_at DESC")->fetchAll();
$vehicles=$pdo->query("SELECT v.*,u.full_name,u.email,u.status AS owner_status,dp.license_no AS driver_license_no,dp.license_doc AS driver_license_doc,dp.languages AS driver_languages,dp.experience_years AS driver_experience,dp.bio AS driver_bio,(SELECT GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') FROM vehicle_parks vp JOIN parks p ON p.id=vp.park_id WHERE vp.vehicle_id=v.id) park_names FROM vehicles v JOIN users u ON u.id=v.owner_id LEFT JOIN driver_profiles dp ON dp.user_id=v.owner_id ORDER BY FIELD(v.approval_status,'pending','rejected','approved'),v.created_at DESC")->fetchAll();
$guides=$pdo->query("SELECT g.*,u.full_name,u.email,u.status AS account_status,(SELECT GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') FROM guide_parks gp JOIN parks p ON p.id=gp.park_id WHERE gp.guide_id=g.user_id) park_names FROM guide_profiles g JOIN users u ON u.id=g.user_id WHERE COALESCE(g.license_no,'')<>'' ORDER BY g.verified ASC,u.full_name ASC")->fetchAll();
dashboard_top('Users & approvals','Operations control');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Approval queue.</h1>
<div class="panel">
    <h2>User accounts</h2>
    <table class="data-table">
        <tr>
            <th>User</th>
            <th>Role</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
<?php
foreach($users as $u):
?>
<tr>
    <td>
<?=
e($u['full_name'])
?>
<br>
<span class="small">
<?=
e($u['email'])
?>
</span>
</td>
<td>
<?=
e($u['role'])
?>
</td>
<td>
<?=
e($u['status'])
?>
</td>
<td>
<?php
if(!in_array($u['role'],['admin','superadmin'])):
?>
<form method="post">
<?=
csrf_field()
?>
<input type="hidden" name="id" value="<?=$u['id']?>">
<button class="btn" name="status" value="active">Approve</button>
<button class="btn ghost" name="status" value="rejected">Reject</button>
</form>
<?php
endif
?>
</td>
</tr>
<?php
endforeach
?>
</table>
</div>
<div class="grid-2">
    <div class="panel">
        <h2>Vehicle verification</h2>
        <p class="small">Review the driver, operating parks and compliance documents here. Drivers submit details; only an administrator can approve or reject a vehicle.</p>
<?php if(!$vehicles): ?><div class="empty-state">No vehicle profiles have been submitted yet.</div><?php endif; ?>
<?php foreach($vehicles as $v): ?>
        <div class="panel" style="padding:16px;margin:14px 0">
            <strong><?=e($v['plate_number']?:'No plate')?> · <?=e($v['full_name'])?></strong>
            <span class="badge <?=e($v['approval_status'])?>"><?=e(ucfirst($v['approval_status']))?></span><br>
            <span class="small"><?=e($v['email'])?> · Driver account: <?=e($v['owner_status'])?></span><br>
            <span class="small">Vehicle: <?=e($v['make_model']?:'—')?> · <?=e(ucfirst((string)$v['safari_type']))?> · Capacity <?=(int)$v['capacity']?></span><br>            <span class="small">Driver licence: <?=e($v['driver_license_no']?:'Not added')?> · Experience: <?=(int)($v['driver_experience']??0)?> years · Languages: <?=e($v['driver_languages']?:'—')?></span><br>
            <?php if($v['driver_license_doc']): ?><a class="btn ghost" href="../uploads/docs/<?=rawurlencode(basename($v['driver_license_doc']))?>" target="_blank" rel="noopener">View driving licence</a><?php endif; ?>

            <span class="small">Parks: <?=e($v['park_names']?:'None selected')?></span><br>
            <span class="small">Registration: <?=e($v['registration_no']?:'—')?> · Insurance: <?=e($v['insurance_no']?:'—')?> · Fitness: <?=e($v['fitness_no']?:'—')?></span>
            <p class="small">
            <?php if($v['registration_doc']): ?><a class="btn ghost" href="../uploads/docs/<?=rawurlencode(basename($v['registration_doc']))?>" target="_blank" rel="noopener">View registration</a><?php else: ?>Registration document: not uploaded<?php endif; ?>
            <?php if($v['insurance_doc']): ?><a class="btn ghost" href="../uploads/docs/<?=rawurlencode(basename($v['insurance_doc']))?>" target="_blank" rel="noopener">View insurance</a><?php else: ?> · Insurance: not uploaded<?php endif; ?>
            <?php if($v['fitness_doc']): ?><a class="btn ghost" href="../uploads/docs/<?=rawurlencode(basename($v['fitness_doc']))?>" target="_blank" rel="noopener">View fitness</a><?php else: ?> · Fitness: not uploaded<?php endif; ?>
            </p>
            <form method="post"><?=csrf_field()?><input type="hidden" name="vehicle" value="<?=(int)$v['id']?>"><button class="btn" name="vstatus" value="approved">Approve vehicle</button> <button class="btn ghost" name="vstatus" value="rejected">Reject vehicle</button></form>
        </div>
<?php endforeach; ?>
    </div>
    <div class="panel">
    <h2>Guide verification</h2>
    <p class="small">All submitted guide profiles stay visible here after approval or rejection so staff can review them again when needed.</p>
<?php if(!$guides): ?><div class="empty-state">No guide profiles have been submitted yet.</div><?php endif; ?>
<?php foreach($guides as $g): ?>
<div class="panel" style="padding:16px;margin:14px 0">
    <strong><?=e($g['full_name'])?></strong> · <?=e($g['license_no'])?>
    <span class="badge <?=!empty($g['verified'])?'approved':'pending'?>"><?=!empty($g['verified'])?'Verified':'Awaiting verification'?></span><br>
    <span class="small"><?=e($g['email'])?> · Guide account: <?=e($g['account_status'])?></span><br>
    <span class="small">Parks: <?=e($g['park_names']?:'None selected')?></span><br>
    <span class="small">Languages: <?=e($g['languages']?:'—')?> · Experience: <?=(int)$g['experience_years']?> years · Fee: LKR <?=number_format((float)$g['guide_fee'],0)?></span>
    <p class="small"><?php if($g['license_doc']): ?><a class="btn ghost" href="../uploads/docs/<?=rawurlencode(basename($g['license_doc']))?>" target="_blank" rel="noopener">View licence document</a><?php else: ?>Licence document: not uploaded<?php endif; ?></p>
    <form method="post"><?=csrf_field()?>
    <input type="hidden" name="guide" value="<?=(int)$g['user_id']?>"><button class="btn" name="gstatus" value="approved"><?=!empty($g['verified'])?'Keep verified':'Verify guide'?></button> <button class="btn ghost" name="gstatus" value="rejected">Reject / unverify</button>
    </form>
</div>
<?php endforeach; ?>
</div>
</div>
<?php
dashboard_bottom();
?>
