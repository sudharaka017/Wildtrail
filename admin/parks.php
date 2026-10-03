<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
if($_SERVER['REQUEST_METHOD']==='POST') {
    $id=(int)($_POST['id']??0);
    $fee=(float)($_POST['fee']??-1);
    $cap=(int)($_POST['cap']??0);
    $status=(string)($_POST['status']??'');
    if($id<1 || $fee<0 || $fee>1000000 || $cap<1 || $cap>1000 || !in_array($status,['active','inactive'],true)) {
        flash('error','Enter valid park settings. Fee cannot be negative, capacity must be 1–1000, and status must be active/inactive.');
    } else {
        $pdo->prepare('UPDATE parks SET entry_fee=?,daily_vehicle_cap=?,status=? WHERE id=?')->execute([$fee,$cap,$status,$id]);
        flash('success','Park settings updated. The daily vehicle cap is enforced during booking.');
    }
    header('Location: parks.php'); exit;
}
$rows=$pdo->query('SELECT * FROM parks ORDER BY name')->fetchAll();
dashboard_top('Parks & capacity','Operations control'); back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Vehicle caps & pricing.</h1>
<div class="panel"><table class="data-table"><tr><th>Park</th><th>Province</th><th>Settings</th></tr>
<?php foreach($rows as $p): ?><tr><td><strong><?=e($p['name'])?></strong></td><td><?=e($p['province'])?></td><td>
<form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="id" value="<?=$p['id']?>">
<input name="fee" type="number" min="0" max="1000000" step="100" value="<?=e($p['entry_fee'])?>" aria-label="Entry fee">
<input name="cap" type="number" min="1" max="1000" value="<?=e($p['daily_vehicle_cap'])?>" aria-label="Daily vehicle cap">
<select name="status"><option value="active" <?=$p['status']==='active'?'selected':''?>>active</option><option value="inactive" <?=$p['status']==='inactive'?'selected':''?>>inactive</option></select>
<button class="btn">Update</button></form></td></tr><?php endforeach; ?></table></div>
<?php dashboard_bottom(); ?>
