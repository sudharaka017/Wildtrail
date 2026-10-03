<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('guide');
$zoneRepo=new \WildTrail\Repositories\ParkZoneRepository($pdo);
$pk=$pdo->prepare("SELECT p.id,p.name FROM parks p JOIN guide_parks gp ON gp.park_id=p.id WHERE gp.guide_id=? AND p.status='active' ORDER BY p.name"); $pk->execute([$_SESSION['user_id']]); $parks=$pk->fetchAll();
$species=$pdo->query('SELECT * FROM species ORDER BY common_name')->fetchAll();
$zones=$zoneRepo->all();
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $parkId=(int)($_POST['park_id']??0);
        $zoneId=(int)($_POST['zone_id']??0);
        $sid=(int)($_POST['species_id']??0);
        $count=(int)($_POST['count']??1);
        $notes=trim($_POST['notes']??'');
        if(strlen($notes)>1000) throw new RuntimeException('Field note must be 1000 characters or fewer.');
        if($count<1 || $count>100) throw new RuntimeException('Animal count must be between 1 and 100.');
        $q=$pdo->prepare('SELECT protected FROM species WHERE id=?');
        $q->execute([$sid]);
        $sp=$q->fetch();
        if(!$sp) throw new RuntimeException('Please select a valid species.');
        $auth=$pdo->prepare('SELECT 1 FROM guide_parks WHERE guide_id=? AND park_id=?'); $auth->execute([$_SESSION['user_id'],$parkId]); if(!$auth->fetchColumn()) throw new RuntimeException('You are not authorised to log sightings for this park.');
        $zq=$pdo->prepare('SELECT id,name,center_lat,center_lng FROM park_zones WHERE id=? AND park_id=? AND is_active=1');
        $zq->execute([$zoneId,$parkId]);
        $zone=$zq->fetch();
        if(!$zone) throw new RuntimeException('Please select a valid zone for this park.');
        $photo=safe_upload('photo',__DIR__.'/../uploads/sightings',['image/jpeg','image/png']);
        $lat=trim($_POST['latitude']??'');
        $lng=trim($_POST['longitude']??'');
        $lat=$lat===''?(float)$zone['center_lat']:(float)$lat;
        $lng=$lng===''?(float)$zone['center_lng']:(float)$lng;
        if($lat < -90 || $lat > 90) throw new RuntimeException('Latitude is invalid.');
        if($lng < -180 || $lng > 180) throw new RuntimeException('Longitude is invalid.');
        // Protected species and unusually large counts are held for staff verification.
        $status=((int)$sp['protected']===1 || $count>20)?'flagged':'normal';
        $pdo->prepare('INSERT INTO wildlife_sightings(guide_id,park_id,species_id,zone_id,zone,count_seen,notes,photo,latitude,longitude,review_status) VALUES(?,?,?,?,?,?,?,?,?,?,?)') ->execute([$_SESSION['user_id'],$parkId,$sid,$zoneId,$zone['name'],$count,$notes,$photo,$lat,$lng,$status]);
        flash('success',$status==='flagged'?'Sighting saved and added to the staff activity map. It is waiting for verification before public display.':'Sighting saved and added to the staff activity map.');
        header('Location: sighting.php');
        exit;
    }
    catch(Throwable $e) {
        flash('error',$e->getMessage());
    }
}
dashboard_top('Log sighting','Guide workspace');
back_button('dashboard.php','Back to dashboard');
?>
<h1 class="dash-title">Report what you see.</h1>
<div class="panel">
    <strong>How the activity map works</strong>
    <p class="small">Choose the park and operational zone, then enter the number of animals observed. Staff zone colours are recalculated from recent reports. Exact GPS is optional; if it is not supplied, the selected zone centre is stored as the map reference.</p>
</div>
<form class="panel" method="post" enctype="multipart/form-data">
<?=
csrf_field()
?>
<div class="form-grid">
    <div class="field">
        <label>Park</label>
        <select name="park_id" id="parkSelect" required>
<?php
foreach($parks as $p):
?>
<option value="<?=$p['id']?>">
<?=
e($p['name'])
?>
</option>
<?php
endforeach
?>
</select>
</div>
<div class="field">
    <label>Operational zone</label>
    <select name="zone_id" id="zoneSelect" required>
<?php
foreach($zones as $z):
?>
<option value="<?=$z['id']?>" data-park="<?=$z['park_id']?>">
<?=
e($z['park_name'].' · '.$z['name'])
?>
</option>
<?php
endforeach
?>
</select>
</div>
</div>
<div class="form-grid">
    <div class="field">
        <label>Species</label>
        <select name="species_id" required>
<?php
foreach($species as $s):
?>
<option value="<?=$s['id']?>">
<?=
e($s['common_name'])
?>
<?=
$s['protected']?' · protected':''
?>
</option>
<?php
endforeach
?>
</select>
</div>
<div class="field">
    <label>Animals observed</label>
    <input type="number" name="count" min="1" max="100" value="1" required>
</div>
</div>
<div class="location-box">
    <div>
        <strong>Precise field location (optional)</strong>
        <p class="small">Use GPS when available. Staff can see precise coordinates; the public view never exposes exact protected-species locations.</p>
    </div>
    <button class="btn ghost" type="button" id="useLocation">Use my current location</button>
</div>
<div class="form-grid">
    <div class="field">
        <label>Latitude</label>
        <input id="latitude" name="latitude" type="number" step="0.0000001" placeholder="Optional">
    </div>
    <div class="field">
        <label>Longitude</label>
        <input id="longitude" name="longitude" type="number" step="0.0000001" placeholder="Optional">
    </div>
</div>
<div class="field">
    <label>Field note</label>
    <textarea name="notes" rows="4" maxlength="1000" placeholder="Behaviour, habitat, direction of travel, safety note..."></textarea>
</div>
<div class="field">
    <label>Optional photo</label>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png">
</div>
<button class="btn">Save sighting</button>
</form>
<script>
    const park=document.getElementById('parkSelect'),zone=document.getElementById('zoneSelect');
    function filterZones(){const id=park.value;let first=null;[...zone.options].forEach(o=>{const show=o.dataset.park===id;o.hidden=!show;o.disabled=!show;if(show&&!first)first=o;});if(first)zone.value=first.value;}
    park.addEventListener('change',filterZones);filterZones();
    document.getElementById('useLocation').addEventListener('click',function(){const b=this;if(!navigator.geolocation){alert('Location is not available in this browser.');return;}b.disabled=true;b.textContent='Finding location…';navigator.geolocation.getCurrentPosition(p=>{document.getElementById('latitude').value=p.coords.latitude.toFixed(7);document.getElementById('longitude').value=p.coords.longitude.toFixed(7);b.textContent='Location captured ✓';b.disabled=false;},()=>{alert('Could not access your location. Check browser permission.');b.textContent='Use my current location';b.disabled=false;},{enableHighAccuracy:true,timeout:10000});});
</script>
<?php
dashboard_bottom();
?>
