<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('tourist');
$id=(int)($_GET['id']??$_POST['id']??0);
$st=$pdo->prepare("SELECT b.*,p.name,gu.full_name guide_name,du.full_name driver_name,v.make_model FROM bookings b JOIN parks p ON p.id=b.park_id LEFT JOIN users gu ON gu.id=b.guide_id LEFT JOIN users du ON du.id=b.driver_id LEFT JOIN vehicles v ON v.id=b.vehicle_id WHERE b.id=? AND b.visitor_id=? AND b.status='completed'");
$st->execute([$id,$_SESSION['user_id']]);
$b=$st->fetch();
if(!$b)exit('Completed booking not found.');
$rv=$pdo->prepare('SELECT guide_rating,vehicle_rating,driver_rating,overall_rating,comment FROM reviews WHERE booking_id=? AND visitor_id=?');
$rv->execute([$id,$_SESSION['user_id']]);
$existing=$rv->fetch() ?: ['guide_rating'=>5,'vehicle_rating'=>5,'driver_rating'=>5,'overall_rating'=>5,'comment'=>''];

if($_SERVER['REQUEST_METHOD']==='POST') {
    foreach(['overall','guide','vehicle','driver'] as $x) {
        $val=(int)($_POST[$x]??0);
        if($val<1||$val>5) {
            flash('error','Please rate each item from 1 to 5.');
            header('Location: review.php?id='.$id);
            exit;
        }
    }
    $pdo->prepare("INSERT INTO reviews(booking_id,visitor_id,guide_rating,vehicle_rating,driver_rating,overall_rating,comment) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE guide_rating=VALUES(guide_rating),vehicle_rating=VALUES(vehicle_rating),driver_rating=VALUES(driver_rating),overall_rating=VALUES(overall_rating),comment=VALUES(comment)")->execute([$id,$_SESSION['user_id'],$_POST['guide'],$_POST['vehicle'],$_POST['driver'],$_POST['overall'],trim($_POST['comment']??'')]);
    flash('success','Thank you. Your review helps future visitors choose responsibly.');
    header('Location: bookings.php');
    exit;
}
dashboard_top('Review safari','Tourist portal');
back_button('bookings.php','Back to my bookings');
?>
<h1 class="dash-title">How was the field day?</h1>
<div class="panel">
    <h2>
<?=
e($b['name'])
?>
</h2>
<p>
<?=
e($b['entry_date'])
?>
· guide
<?=
e($b['guide_name'])
?>
·
<?=
e($b['make_model'])
?>
</p>
<form method="post">
<?=
csrf_field()
?>
<input type="hidden" name="id" value="<?=$id?>">
<div class="form-grid">
    <div class="field">
        <label>Overall safari</label>
        <select name="overall">
<?php
for($i=5;$i>=1;$i--):
?>
<option value="<?=$i?>" <?=((int)$existing['overall_rating']===$i)?'selected':''?>>
<?=
$i
?>
/ 5</option>
<?php
endfor
?>
</select>
</div>
<div class="field">
    <label>Guide ·
<?=
e($b['guide_name'])
?>
</label>
<select name="guide">
<?php
for($i=5;$i>=1;$i--):
?>
<option value="<?=$i?>" <?=((int)$existing['guide_rating']===$i)?'selected':''?>>
<?=
$i
?>
/ 5</option>
<?php
endfor
?>
</select>
</div>
</div>
<div class="form-grid">
    <div class="field">
        <label>Vehicle ·
<?=
e($b['make_model'])
?>
</label>
<select name="vehicle">
<?php
for($i=5;$i>=1;$i--):
?>
<option value="<?=$i?>" <?=((int)$existing['vehicle_rating']===$i)?'selected':''?>>
<?=
$i
?>
/ 5</option>
<?php
endfor
?>
</select>
</div>
<div class="field">
    <label>Driver ·
<?=
e($b['driver_name'])
?>
</label>
<select name="driver">
<?php
for($i=5;$i>=1;$i--):
?>
<option value="<?=$i?>" <?=((int)$existing['driver_rating']===$i)?'selected':''?>>
<?=
$i
?>
/ 5</option>
<?php
endfor
?>
</select>
</div>
</div>
<div class="field">
    <label>Comment</label>
    <textarea name="comment" rows="5" maxlength="1500" placeholder="What was especially good, and what should future visitors know?"><?=e($existing['comment'])?></textarea>
</div>
<button class="btn">Publish review</button>
</form>
</div>
<?php
dashboard_bottom();
?>
