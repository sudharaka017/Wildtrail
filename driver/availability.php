<?php
require '../includes/bootstrap.php';
require '../includes/layout.php';
require_role('driver');
$slots=['dawn'=>'Dawn','morning'=>'Morning','afternoon'=>'Afternoon'];
$today=new DateTimeImmutable('today');
$month=$_GET['month']??date('Y-m');
if($_SERVER['REQUEST_METHOD']==='POST'){
    $month=$_POST['month']??'';
    if(!preg_match('/^\d{4}-\d{2}$/',$month)){
        flash('error','Choose a valid month.'); header('Location: availability.php'); exit;
    }
    $start=DateTimeImmutable::createFromFormat('!Y-m-d',$month.'-01');
    if(!$start){ flash('error','Choose a valid month.'); header('Location: availability.php'); exit; }
    $end=$start->modify('last day of this month');
    $maxMonth=$today->modify('+12 months')->format('Y-m');
    if($month<$today->format('Y-m') || $month>$maxMonth){ flash('error','You can manage the current month and the next 12 months.'); header('Location: availability.php'); exit; }
    $chosen=$_POST['availability']??[];
    $sql='INSERT INTO driver_availability(driver_id,available_date,slot,is_available) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE is_available=VALUES(is_available)';
    $up=$pdo->prepare($sql);
    try{
        $pdo->beginTransaction();
        for($d=$start;$d<=$end;$d=$d->modify('+1 day')){
            if($d<$today) continue;
            $date=$d->format('Y-m-d');
            foreach($slots as $key=>$label){
                $on=isset($chosen[$date][$key])?1:0;
                $up->execute([$_SESSION['user_id'],$date,$key,$on]);
            }
        }
        $pdo->commit();
        flash('success','Your '.$start->format('F Y').' availability was saved in one update.');
    }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); flash('error','Availability could not be saved. Please try again.'); }
    header('Location: availability.php?month='.urlencode($month)); exit;
}
if(!preg_match('/^\d{4}-\d{2}$/',$month)) $month=date('Y-m');
$monthStart=DateTimeImmutable::createFromFormat('!Y-m-d',$month.'-01') ?: $today->modify('first day of this month');
if($monthStart->format('Y-m')<$today->format('Y-m')) $monthStart=$today->modify('first day of this month');
$monthEnd=$monthStart->modify('last day of this month');
$month=$monthStart->format('Y-m');
$st=$pdo->prepare('SELECT available_date,slot,is_available FROM driver_availability WHERE driver_id=? AND available_date BETWEEN ? AND ?');
$st->execute([$_SESSION['user_id'],$monthStart->format('Y-m-d'),$monthEnd->format('Y-m-d')]);
$state=[]; foreach($st->fetchAll() as $r){$state[$r['available_date']][$r['slot']]=(int)$r['is_available'];}
$bk=$pdo->prepare("SELECT b.entry_date,ps.slot_key FROM bookings b JOIN park_slots ps ON ps.id=b.slot_id WHERE b.driver_id=? AND b.entry_date BETWEEN ? AND ? AND b.status IN('pending','confirmed')");
$bk->execute([$_SESSION['user_id'],$monthStart->format('Y-m-d'),$monthEnd->format('Y-m-d')]);
$booked=[]; foreach($bk->fetchAll() as $r){$k=(string)$r['slot_key']; if(in_array($k,['dawn','morning','afternoon'],true)) $booked[$r['entry_date']][$k]=true;}
$prev=$monthStart->modify('-1 month')->format('Y-m'); $next=$monthStart->modify('+1 month')->format('Y-m');
dashboard_top('Availability','Driver workspace'); back_button('dashboard.php','Back to dashboard');
?>
<div class="availability-head"><div><h1 class="dash-title">Monthly availability.</h1><p class="section-copy">Set your whole month once. New dates start unavailable. Tick the safari times you can drive, then save the month.</p></div><form method="get" class="month-jump"><label>Month</label><input type="month" name="month" min="<?=e($today->format('Y-m'))?>" max="<?=e($today->modify('+12 months')->format('Y-m'))?>" value="<?=e($month)?>"><button class="btn">Open month</button></form></div>
<div class="panel availability-tools"><div><strong><?=e($monthStart->format('F Y'))?></strong><small>Changes are saved for the entire month with one button.</small></div><div class="month-nav"><?php if($prev >= $today->format('Y-m')): ?><a class="btn ghost" href="?month=<?=e($prev)?>">← Previous</a><?php endif; ?><a class="btn ghost" href="?month=<?=e($next)?>">Next →</a></div></div>
<form method="post" id="monthAvailability"><?php echo csrf_field(); ?><input type="hidden" name="month" value="<?=e($month)?>">
<div class="panel"><div class="availability-actions"><button type="button" class="btn ghost" data-month-action="all">Mark all available</button><button type="button" class="btn ghost" data-month-action="none">Mark all unavailable</button><span class="small">Booked safari times stay visible and cannot be changed here.</span></div>
<div class="table-wrap"><table class="data-table availability-table"><thead><tr><th>Date</th><?php foreach($slots as $label): ?><th><?=e($label)?></th><?php endforeach; ?></tr></thead><tbody>
<?php for($d=$monthStart;$d<=$monthEnd;$d=$d->modify('+1 day')): $date=$d->format('Y-m-d'); $past=$d<$today; ?>
<tr class="<?=$past?'past-day':''?>"><td><strong><?=e($d->format('D, d M'))?></strong><?php if($date===$today->format('Y-m-d')):?><span class="badge">Today</span><?php endif;?></td>
<?php foreach($slots as $key=>$label): $isBooked=!empty($booked[$date][$key]); $checked=isset($state[$date][$key]) && $state[$date][$key]===1; ?>
<td><label class="availability-cell <?=$isBooked?'booked':''?>"><input class="availability-check" type="checkbox" name="availability[<?=e($date)?>][<?=e($key)?>]" <?=$checked?'checked':''?> <?=($past||$isBooked)?'disabled':''?>> <span><?=$isBooked?'Booked':($checked?'Available':'Unavailable')?></span></label><?php if($isBooked): ?><input type="hidden" name="availability[<?=e($date)?>][<?=e($key)?>]" value="1"><?php endif; ?></td>
<?php endforeach; ?></tr><?php endfor; ?>
</tbody></table></div><div class="save-month-bar"><div><strong>Ready?</strong><small>One click updates every editable safari time in <?=e($monthStart->format('F'))?>.</small></div><button class="btn" type="submit">Save whole month</button></div></div></form>
<script>
(function(){const form=document.getElementById('monthAvailability');if(!form)return;const boxes=[...form.querySelectorAll('.availability-check:not(:disabled)')];function paint(b){const s=b.parentElement.querySelector('span');if(s)s.textContent=b.checked?'Available':'Unavailable';}boxes.forEach(b=>b.addEventListener('change',()=>paint(b)));document.querySelectorAll('[data-month-action]').forEach(btn=>btn.addEventListener('click',()=>{const on=btn.dataset.monthAction==='all';boxes.forEach(b=>{b.checked=on;paint(b);});}));})();
</script>
<?php dashboard_bottom(); ?>
