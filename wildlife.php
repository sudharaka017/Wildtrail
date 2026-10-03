<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';
$repo=new \WildTrail\Repositories\WildlifeRepository($pdo);
$activityService=new \WildTrail\Services\WildlifeActivityService();
$species=$repo->species();
$recent=$repo->recent(8,true);
$parkId=(int)($_GET['park']??0);
$speciesId=(int)($_GET['species']??0);
$days=(int)($_GET['days']??7);
if(!in_array($days,[1,7,30],true))$days=7;
$parks=$pdo->query("SELECT id,name FROM parks WHERE status='active' ORDER BY name")->fetchAll();
$zones=$repo->zoneActivity($parkId,$speciesId,$days,true);
$zoneData=[];
foreach($zones as $z) {
    $level=$activityService->level((int)$z['animal_count']);
    $zoneData[]=['park'=>$z['park_name'],'zone'=>$z['zone_name'],'lat'=>(float)$z['center_lat'],'lng'=>(float)$z['center_lng'],'radius'=>(int)$z['radius_m'],'animals'=>(int)$z['animal_count'],'reports'=>(int)$z['report_count'],'level'=>$level['label'],'color'=>$level['color']];
}
page_top('Wildlife & impact');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<section class="section">
    <h1 class="display" style="color:var(--ink);font-size:78px">The park is <em>alive.</em>
    </h1>
    <div class="grid-2">
        <div>
            <h2>Know who<br>you may meet.</h2>
            <div class="park-list" style="grid-template-columns:repeat(2,1fr)">
<?php
foreach($species as $s):
?>
<article class="park-card" style="min-height:360px">
    <img src="<?=e($s['image_url'])?>" alt="<?=e($s['common_name'])?>">
<div class="card-content">
    <div class="eyebrow">
<?=
e($s['category'])
?>
<?=
$s['protected']?' · protected':''
?>
</div>
<h3>
<?=
e($s['common_name'])
?>
</h3>
<div class="small" style="color:#ddd">
<?=
e($s['scientific_name'])
?>
</div>
</div>
</article>
<?php
endforeach
?>
</div>
</div>
<div>
    <h2>Verified recent<br>
        <em>sightings.</em>
    </h2>
    <div class="panel">
        <table class="data-table">
            <tbody>
<?php
if(!$recent):
?>
<tr>
    <td>No verified sightings yet.</td>
</tr>
<?php
endif;
foreach($recent as $r):
?>
<tr>
    <td>
        <strong>
<?=
e($r['common_name'])
?>
</strong>
<br>
<span class="small">
<?=
e($r['park_name'])
?>
·
<?=
e($r['zone_name']?:$r['zone'])
?>
</span>
</td>
<td>×
<?=
e($r['count_seen'])
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
</section>
<section class="section map-public">
    <div class="section-head">
        <div>
            <div class="eyebrow">Verified zone activity</div>
            <h2>Wildlife activity,<br>
                <em>mapped responsibly.</em>
            </h2>
        </div>
        <p class="section-copy">This view shows verified activity by broad operational zone, not exact animal positions. Zone circles are a system visualization and are not official park boundary polygons.</p>
    </div>
    <form method="get" class="panel heat-filter">
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
>24 hours</option>
<option value="7"
<?=
$days===7?'selected':''
?>
>7 days</option>
<option value="30"
<?=
$days===30?'selected':''
?>
>30 days</option>
</select>
<button class="btn">Show activity</button>
</form>
<div class="activity-legend">
    <span>
        <i style="background:#8b949e">
        </i>No activity</span>
        <span>
            <i style="background:#2e8b57">
            </i>Low</span>
            <span>
                <i style="background:#e5b80b">
                </i>Moderate</span>
                <span>
                    <i style="background:#f28c28">
                    </i>High</span>
                    <span>
                        <i style="background:#d64545">
                        </i>Very high</span>
                    </div>
                    <div id="publicZoneMap" class="heatmap public-map">
                    </div>
                </section>
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
                </script>
                <script>
                    const zones=
<?=
json_encode($zoneData,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
?>
;const map=L.map('publicZoneMap',{scrollWheelZoom:false}).setView([7.6,80.7],7);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap contributors'}).addTo(map);const bounds=[];zones.forEach(z=>{L.circle([z.lat,z.lng],{radius:z.radius,color:z.color,fillColor:z.color,fillOpacity:.42,weight:2}).bindPopup(`<strong>${z.park} · ${z.zone}</strong>
<br>${z.level}<br>Verified animals observed: ${z.animals}<br>Verified reports: ${z.reports}`).addTo(map);L.marker([z.lat,z.lng],{opacity:0}).bindTooltip(`${z.zone}`,{permanent:true,direction:'center',className:'zone-label'}).addTo(map);bounds.push([z.lat,z.lng]);});if(bounds.length)map.fitBounds(bounds,{padding:[30,30],maxZoom:11});
</script>
<?php
page_bottom();
?>
