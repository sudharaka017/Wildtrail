<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';
page_top('Explore parks');
$parks=$pdo->query("SELECT * FROM parks WHERE status='active' ORDER BY name")->fetchAll();
?>
<section class="section">
    <h1 class="display" style="color:var(--ink);font-size:78px">Choose your <em>landscape.</em>
    </h1>
    <div class="park-list">
<?php
foreach($parks as $p):
?>
<article class="park-tile" id="
<?=
e($p['slug'])
?>
">
<img src="<?=e($p['image_url'])?>" alt="<?=e($p['name'])?>">
<div class="body">
    <div class="eyebrow">
<?=
e($p['province'])
?>
</div>
<h3>
<?=
e($p['name'])
?>
</h3>
<p class="section-copy">
<?=
e($p['description'])
?>
</p>
<p>
    <strong>LKR
<?=
number_format($p['entry_fee'],0)
?>
</strong> · daily vehicle cap
<?=
e($p['daily_vehicle_cap'])
?>
</p>
<div style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn ghost" href="park.php?slug=<?=e($p['slug'])?>">View park</a>
<a class="btn" href="tourist/book.php?park=<?=e($p['id'])?>">Book safari</a>
</div>
</div>
</article>
<?php
endforeach
?>
</div>
</section>
<?php
page_bottom();
?>
