<?php
require 'includes/bootstrap.php';
require 'includes/layout.php';
page_top('Safari discovery');
$parks=$pdo->query("SELECT * FROM parks WHERE status='active' ORDER BY id LIMIT 3")->fetchAll();
?>
<section class="hero">
    <div class="hero-inner">
        <h1 class="display">Go where the wild <em>still</em> leads.</h1>
        <p class="lead">Plan a responsible national-park safari with verified drivers, licensed guides, controlled vehicle capacity, online payments and a digital permit—all in one place.</p>
        <div class="hero-actions">
            <a class="btn orange" href="parks.php">Explore the parks →</a>
            <a class="btn ghost" style="color:white;border-color:#ffffff66" href="register.php">Plan a safari</a>
        </div>
        <div class="stats">
            <div>
                <strong>4</strong>
                <span>featured parks</span>
            </div>
            <div>
                <strong>5</strong>
                <span>system roles</span>
            </div>
            <div>
                <strong>1</strong>
                <span>shared responsibility</span>
            </div>
        </div>
    </div>
</section>
<section class="section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Start with the landscape</div>
            <h2>Park notes,<br>
                <em>not brochures.</em>
            </h2>
        </div>
        <p class="section-copy">Browse park context, compare entry prices and see the capacity model before you commit to a route.</p>
    </div>
    <div class="cards">
<?php
foreach($parks as $p):
?>
<a class="park-card" href="parks.php#<?=e($p['slug'])?>">
<img src="<?=e($p['image_url'])?>" alt="<?=e($p['name'])?>">
<div class="card-content">
    <div class="eyebrow">Featured route</div>
    <h3>
<?=
e($p['name'])
?>
</h3>
<p>
<?=
e($p['province'])
?>
</p>
</div>
</a>
<?php
endforeach
?>
</div>
</section>
<section class="section split">
    <div>
        <div class="eyebrow">Conservation first</div>
        <h2 class="editorial-title">The best sighting leaves <em>no trace.</em>
        </h2>
    </div>
    <div>
        <p class="section-copy">The platform connects booking data with guide observations so administrators can see operational pressure and conservation signals in the same system.</p>
        <div class="metric-row">
            <div>
                <strong>RBAC</strong>
                <div class="small">role security</div>
            </div>
            <div>
                <strong>CSRF</strong>
                <div class="small">form protection</div>
            </div>
            <div>
                <strong>PDO</strong>
                <div class="small">prepared SQL</div>
            </div>
        </div>
    </div>
</section>
<?php
page_bottom();
?>
