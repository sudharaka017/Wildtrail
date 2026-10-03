<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';
$slug=trim($_GET['slug']??'');
$st=$pdo->prepare("SELECT * FROM parks WHERE slug=? AND status='active' LIMIT 1");
$st->execute([$slug]);
$park=$st->fetch();
if(!$park){ http_response_code(404); exit('Park not found.'); }
$im=$pdo->prepare('SELECT * FROM park_images WHERE park_id=? ORDER BY sort_order,id');
$im->execute([$park['id']]);
$images=$im->fetchAll();
$slots=$pdo->prepare('SELECT * FROM park_slots WHERE park_id=? AND is_active=1 ORDER BY start_time');
$slots->execute([$park['id']]);
$slots=$slots->fetchAll();

$parkFacts=[
'yala'=>[
  'summary'=>'A south-eastern dry-zone park where scrub forest, open plains, rocky outcrops, lagoons and coastal habitats meet.',
  'wildlife'=>['Sri Lankan leopard','Asian elephant','Sloth bear','Spotted deer','Crocodiles','Rich birdlife'],
  'highlights'=>['Leopard-focused safari landscape','Wetlands and lagoons','Dry forest and open plains','Ancient cultural sites around the wider Yala area'],
  'visit'=>'Wildlife activity changes with weather and season. Check current park notices before travel because access to individual blocks can change.',
  'source'=>'https://www.srilanka.travel/attraction?attraction_id=163'
],
'udawalawe'=>[
  'summary'=>'An open landscape shaped by the Udawalawe Reservoir, grasslands, marshes, scrub and dry forest, especially well known for elephants.',
  'wildlife'=>['Asian elephant','Water buffalo','Sambar deer','Crocodiles','Raptors','Waterbirds'],
  'highlights'=>['Open elephant-viewing country','Reservoir scenery','Grassland habitats','Strong birdwatching opportunities'],
  'visit'=>'Its open terrain makes it a practical choice for visitors who want broad views across grassland and reservoir-edge habitats.',
  'source'=>'https://www.srilanka.travel/wild-safaris?article=72'
],
'minneriya'=>[
  'summary'=>'A North Central dry-zone park centred on the ancient Minneriya tank, with grasslands, woodland and permanent water supporting diverse wildlife.',
  'wildlife'=>['Asian elephant','Spotted deer','Sambar deer','Macaques','Crocodiles','Waterbirds'],
  'highlights'=>['Minneriya tank','Seasonal elephant concentrations','Open grassland','Dry-zone woodland'],
  'visit'=>'Elephant numbers and locations are seasonal, so visitors should treat sightings as wildlife encounters rather than guarantees.',
  'source'=>'https://www.srilanka.travel/wild-safaris'
],
'wilpattu'=>[
  'summary'=>'A large north-western dry-zone park famous for its natural rain-fed lakes, locally called villus, set among forest and scrub.',
  'wildlife'=>['Sri Lankan leopard','Sloth bear','Asian elephant','Spotted deer','Water buffalo','Wetland birds'],
  'highlights'=>['Natural villus','Large forest landscape','Leopard habitat','Sloth-bear habitat'],
  'visit'=>'The villus are a defining feature of Wilpattu and create distinctive wildlife-viewing landscapes across the park.',
  'source'=>'https://www.srilanka.travel/attraction?attraction_id=219'
]
];
$f=$parkFacts[$slug]??null;
page_top($park['name']);
?>
<section class="park-hero" style="background-image:linear-gradient(90deg,rgba(8,44,36,.88),rgba(8,44,36,.18)),url('<?=e($park['image_url'])?>')">
<div><div class="eyebrow"><?=e($park['province'])?></div><h1 class="display"><?=e($park['name'])?></h1><p class="lead"><?=e($park['description'])?></p><a class="btn orange" href="tourist/book.php?park=<?=$park['id']?>">Check dates & book safari →</a></div>
</section>
<?php if($f): ?>
<section class="section park-about">
  <div class="section-head"><div><div class="eyebrow">Know before you book</div><h2>Discover <em><?=e($park['name'])?>.</em></h2></div><p class="section-copy"><?=e($f['summary'])?></p></div>
  <div class="park-fact-grid">
    <article class="park-fact-card"><span>Wildlife you may encounter</span><div class="tags dark"><?php foreach($f['wildlife'] as $x): ?><span class="tag"><?=e($x)?></span><?php endforeach; ?></div></article>
    <article class="park-fact-card"><span>Landscape highlights</span><ul><?php foreach($f['highlights'] as $x): ?><li><?=e($x)?></li><?php endforeach; ?></ul></article>
    <article class="park-fact-card"><span>Visitor note</span><p><?=e($f['visit'])?></p><a class="text-link" target="_blank" rel="noopener" href="<?=e($f['source'])?>">Sri Lanka Tourism reference ↗</a></article>
  </div>
</section>
<?php endif; ?>
<section class="section">
  <div class="section-head"><div><div class="eyebrow">Photo story</div><h2>See the <em>real landscape.</em></h2></div><p class="section-copy"><?=count($images)?> park-specific photographs are shown with captions and source credits where available.</p></div>
  <div class="park-gallery park-gallery-rich">
  <?php foreach($images as $i): ?><figure><img loading="lazy" src="<?=e($i['image_url'])?>" alt="<?=e($i['caption'])?>"><figcaption><span><?=e($i['caption'])?></span><?php if($i['source_url']): ?><a target="_blank" rel="noopener" href="<?=e($i['source_url'])?>">Photo: <?=e($i['credit'])?> ↗</a><?php endif; ?></figcaption></figure><?php endforeach; ?>
  </div>
</section>
<section class="section split"><div><div class="eyebrow">Safari planning</div><h2>Choose a time<br><em>with room to breathe.</em></h2><p class="section-copy">Availability is checked before booking, so tourists can immediately see whether a safari time still has space.</p></div><div class="panel"><p><strong>Entry from LKR <?=number_format($park['entry_fee'],0)?></strong></p><?php foreach($slots as $s): ?><div class="slot-row"><span><?=e($s['label'])?><small><?=e(substr($s['start_time'],0,5))?>–<?=e(substr($s['end_time'],0,5))?></small></span><strong><?=$s['vehicle_cap']?> vehicles</strong></div><?php endforeach; ?><a class="btn" href="tourist/book.php?park=<?=$park['id']?>">Check live availability</a></div></section>
<?php page_bottom(); ?>
