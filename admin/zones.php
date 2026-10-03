<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
$zoneRepo=new \WildTrail\Repositories\ParkZoneRepository($pdo);
$parks=$pdo->query("SELECT id,name FROM parks ORDER BY name")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $id=(int)($_POST['id']??0);
        $parkId=(int)($_POST['park_id']??0);
        $name=trim($_POST['name']??'');
        $code=strtoupper(trim($_POST['code']??''));
        $lat=(float)($_POST['center_lat']??0);
        $lng=(float)($_POST['center_lng']??0);
        $radius=max(250,min(20000,(int)($_POST['radius_m']??2500)));
        $active=isset($_POST['is_active'])?1:0;
        if(!$parkId||$name===''||$code==='')throw new RuntimeException('Park, zone name and code are required.');
        if($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)throw new RuntimeException('Zone coordinates are invalid.');
        if($id) {
            $st=$pdo->prepare('UPDATE park_zones SET park_id=?,name=?,code=?,center_lat=?,center_lng=?,radius_m=?,is_active=? WHERE id=?');
            $st->execute([$parkId,$name,$code,$lat,$lng,$radius,$active,$id]);
        }
        else {
            $st=$pdo->prepare('INSERT INTO park_zones(park_id,name,code,center_lat,center_lng,radius_m,is_active) VALUES(?,?,?,?,?,?,?)');
            $st->execute([$parkId,$name,$code,$lat,$lng,$radius,$active]);
        }
        flash('success','Operational zone saved.');
        header('Location: zones.php');
        exit;
    }
    catch(Throwable $e) {
        flash('error',$e->getMessage());
    }
}
$zones=$zoneRepo->all();
$editId=(int)($_GET['edit']??0);
$edit=null;
foreach($zones as $z) {
    if((int)$z['id']===$editId) {
        $edit=$z;
        break;
    }
}
dashboard_top('Park zones','Conservation intelligence');
back_button('dashboard.php','Back to dashboard');
?>
<div class="eyebrow">Map configuration</div>
<h1 class="dash-title">Operational park zones.</h1>
<div class="panel">
    <p>
        <strong>Note:</strong> These circles are operational areas used by WildTrail Lanka for activity visualization. They are not official DWC boundary polygons. If authoritative zone geometry becomes available later, replace these centres/radii with that approved data.</p>
    </div>
    <div class="grid-2">
        <form class="panel" method="post">
<?=
csrf_field()
?>
<input type="hidden" name="id" value="<?=$edit['id']??0?>">
<h2>
<?=
$edit?'Edit zone':'Add zone'
?>
</h2>
<div class="field">
    <label>Park</label>
    <select name="park_id" required>
<?php
foreach($parks as $p):
?>
<option value="<?=$p['id']?>"
<?=
isset($edit['park_id'])&&(int)$edit['park_id']===(int)$p['id']?'selected':''
?>
>
<?=
e($p['name'])
?>
</option>
<?php
endforeach
?>
</select>
</div>
<div class="form-grid">
    <div class="field">
        <label>Zone name</label>
        <input name="name" required value="<?=e($edit['name']??'')?>" placeholder="Zone A">
</div>
<div class="field">
    <label>Code</label>
    <input name="code" required value="<?=e($edit['code']??'')?>" placeholder="A">
</div>
</div>
<div class="form-grid">
    <div class="field">
        <label>Centre latitude</label>
        <input type="number" step="0.0000001" name="center_lat" required value="<?=e($edit['center_lat']??'')?>">
</div>
<div class="field">
    <label>Centre longitude</label>
    <input type="number" step="0.0000001" name="center_lng" required value="<?=e($edit['center_lng']??'')?>">
</div>
</div>
<div class="field">
    <label>Display radius (metres)</label>
    <input type="number" min="250" max="20000" name="radius_m" value="<?=e($edit['radius_m']??2500)?>">
</div>
<label class="form-check">
    <input type="checkbox" name="is_active"
<?=
!isset($edit['is_active'])||$edit['is_active']?'checked':''
?>
> Active zone</label>
<button class="btn">Save zone</button>
<?php
if($edit):
?>
<a class="btn ghost" href="zones.php">Cancel</a>
<?php
endif
?>
</form>
<div class="panel">
    <h2>Configured zones</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Park</th>
                    <th>Zone</th>
                    <th>Radius</th>
                    <th>Status</th>
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>
<?php
foreach($zones as $z):
?>
<tr>
    <td>
<?=
e($z['park_name'])
?>
</td>
<td>
    <strong>
<?=
e($z['name'])
?>
</strong>
<br>
<span class="small">
<?=
e($z['code'])
?>
</span>
</td>
<td>
<?=
number_format((int)$z['radius_m'])
?>
m</td>
<td>
<?=
$z['is_active']?'Active':'Inactive'
?>
</td>
<td>
    <a class="btn ghost" href="?edit=<?=$z['id']?>">Edit</a>
</td>
</tr>
<?php
endforeach
?>
</tbody>
</table>
</div>
</div>
</div>
<?php
dashboard_bottom();
?>
