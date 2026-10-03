<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role(['admin','superadmin']);
$repo=new \WildTrail\Repositories\WildlifeRepository($pdo);
$activityService=new \WildTrail\Services\WildlifeActivityService();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['review_sighting'])) {
    $id=(int)($_POST['sighting_id']??0);
    $status=$_POST['status']??'';
    if(!in_array($status,['verified','flagged'],true)) {
        $status='flagged';
    }
    $st=$pdo->prepare('UPDATE wildlife_sightings SET review_status=? WHERE id=?');
    $st->execute([$status,$id]);
    if($st->rowCount()===1) flash('success',$status==='verified'?'Sighting verified. Public activity can now include it.':'Sighting kept flagged for review.'); else flash('error','Sighting not found.');
    $qs=http_build_query(['park'=>(int)($_GET['park']??0),'species'=>(int)($_GET['species']??0),'days'=>(int)($_GET['days']??7)]);
    header('Location: heatmap.php'.($qs?'?'.$qs:''));
    exit;
}
$parkId=(int)($_GET['park']??0);
$speciesId=(int)($_GET['species']??0);
$days=(int)($_GET['days']??7);
if(!in_array($days,[1,7,30,90],true))$days=7;
$parks=$pdo->query('SELECT id,name FROM parks ORDER BY name')->fetchAll();
$species=$repo->species();
$zones=$repo->zoneActivity($parkId,$speciesId,$days,false);
$rows=$repo->detailedSightings($parkId,$speciesId,$days);
$zoneData=[];
foreach($zones as $z) {
    $level=$activityService->level((int)$z['animal_count']);
    $zoneData[]=['id'=>(int)$z['id'],'park'=>$z['park_name'],'zone'=>$z['zone_name'],'code'=>$z['code'],'lat'=>(float)$z['center_lat'],'lng'=>(float)$z['center_lng'],'radius'=>(int)$z['radius_m'],'reports'=>(int)$z['report_count'],'animals'=>(int)$z['animal_count'],'last'=>$z['last_seen'],'level'=>$level['label'],'color'=>$level['color']];
}
dashboard_top('Wildlife activity map','Conservation intelligence');
back_button('dashboard.php','Back to dashboard');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="eyebrow">Zone-based wildlife activity</div>
<h1 class="dash-title">See which park zones are active.</h1>
<div class="panel">
    <p>
        <strong>Important:</strong> These are configurable operational zones for this system, not official Department of Wildlife Conservation boundary polygons. A guide report affects its selected zone immediately on the staff map. Protected-species reports remain flagged until an administrator verifies them for public display.</p>
    </div>
    <div class="panel">
        <form method="get" class="heat-filter">
            <select name="park">
                <option value="0">All parks</option>
<?php
foreach($parks as $p):
?>
<option value="<?=$p['id']?>"
<?=
$parkId===(int)$p['id']?'selected':''
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
<select name="species">
    <option value="0">All species</option>
<?php
foreach($species as $s):
?>
<option value="<?=$s['id']?>"
<?=
$speciesId===(int)$s['id']?'selected':''
?>
>
<?=
e($s['common_name'])
?>
</option>
<?php
endforeach
?>
</select>
<select name="days">
    <option value="1"
<?=
$days===1?'selected':''
?>
>Last 24 hours</option>
<option value="7"
<?=
$days===7?'selected':''
?>
>Last 7 days</option>
<option value="30"
<?=
$days===30?'selected':''
?>
>Last 30 days</option>
<option value="90"
<?=
$days===90?'selected':''
?>
>Last 90 days</option>
</select>
<button class="btn">Apply filter</button>
<a class="btn ghost" href="heatmap.php">Reset</a>
</form>
</div>
<div class="activity-legend">
    <span>
        <i style="background:#8b949e">
        </i>No activity</span>
        <span>
            <i style="background:#2e8b57">
            </i>1–3 animals</span>
            <span>
                <i style="background:#e5b80b">
                </i>4–7</span>
                <span>
                    <i style="background:#f28c28">
                    </i>8–12</span>
                    <span>
                        <i style="background:#d64545">
                        </i>13+</span>
                    </div>
                    <div class="map-grid">
                        <div class="panel">
                            <div id="zoneMap" class="heatmap">
                            </div>
                            <p class="small">Map refreshes every 60 seconds. Reload at any time to see a report immediately.</p>
                        </div>
                        <div class="panel">
                            <h2>Zone activity</h2>
                            <div class="table-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Zone</th>
                                            <th>Animals</th>
                                            <th>Reports</th>
                                            <th>Level</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
foreach($zoneData as $z):
?>
<tr>
    <td>
        <strong>
<?=
e($z['park'])
?>
</strong>
<br>
<span class="small">
<?=
e($z['zone'])
?>
</span>
</td>
<td>
<?=
$z['animals']
?>
</td>
<td>
<?=
$z['reports']
?>
</td>
<td>
<?=
e($z['level'])
?>
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
<div class="panel">
    <h2>Recent reports & verification</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Species</th>
                    <th>Park / zone</th>
                    <th>Guide</th>
                    <th>Count</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
<?php
if(!$rows):
?>
<tr>
    <td colspan="7">No reports in this period.</td>
</tr>
<?php
endif;
foreach(array_slice($rows,0,40) as $r):
?>
<tr>
    <td>
        <strong>
<?=
e($r['common_name'])
?>
</strong>
<?=
$r['protected']?' · protected':''
?>
</td>
<td>
<?=
e($r['park_name'])
?>
<br>
<span class="small">
<?=
e($r['zone_name']?:$r['zone'])
?>
</span>
</td>
<td>
<?=
e($r['guide_name'])
?>
</td>
<td>×
<?=
e($r['count_seen'])
?>
</td>
<td>
<?=
e($r['observed_at'])
?>
</td>
<td>
<?=
e($r['review_status'])
?>
</td>
<td>
    <form method="post" style="display:flex;gap:6px;flex-wrap:wrap">
<?=
csrf_field()
?>
<input type="hidden" name="review_sighting" value="1">
<input type="hidden" name="sighting_id" value="<?=$r['id']?>">
<button class="btn" name="status" value="verified">Verify</button>
<button class="btn ghost" name="status" value="flagged">Flag</button>
</form>
</td>
</tr>
<?php
endforeach
?>
</tbody>
</table>
</div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>
<script>
    const zones=
<?=
json_encode($zoneData,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
?>
;
const map=L.map('zoneMap').setView([7.6,80.7],7);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap contributors'}).addTo(map);
const bounds=[];zones.forEach(z=>{const c=L.circle([z.lat,z.lng],{radius:z.radius,color:z.color,fillColor:z.color,fillOpacity:.46,weight:2}).addTo(map);c.bindPopup(`<strong>${z.park} · ${z.zone}</strong>
<br>${z.level}<br>Animals observed: ${z.animals}<br>Reports: ${z.reports}<br>Last report: ${z.last||'None'}`);L.marker([z.lat,z.lng],{opacity:0}).bindTooltip(`${z.zone}: ${z.animals}`,{permanent:true,direction:'center',className:'zone-label'}).addTo(map);bounds.push([z.lat,z.lng]);});if(bounds.length)map.fitBounds(bounds,{padding:[30,30],maxZoom:12});
setInterval(()=>location.reload(),60000);
</script>
<?php
dashboard_bottom();
?>
