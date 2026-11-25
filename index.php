<?php require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/medals.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/sport_details.php';
$leaders = medal_standings();
$tallyUpdatedAt = medal_tally_updated_at();
$hasMedals = array_sum(array_map(fn($team) => (int)$team['gold'] + (int)$team['silver'] + (int)$team['bronze'], $leaders)) > 0;
$sports = db()->query('SELECT * FROM sports WHERE active=1 ORDER BY name')->fetchAll();
page_head('Live Medal Tracker','home');
?>
<section class="hero">
<div>
<span class="eyebrow">DepEd Leyte Division · Official results portal</span>
<h1>DepEd Leyte Division<br>Athletic Meet 2026</h1>
<p>The official tracker for the DepEd Leyte Division Athletic Meet 2026. Follow verified medal standings, sports results, event schedules, and competition updates in one place.</p>
<div class="hero-event-meta">
<div class="hero-date">05—09 DECEMBER 2026 · JARO, LEYTE</div>
<div class="games-countdown" data-games-start="2026-12-05T00:00:00+08:00" aria-live="polite">
<span class="countdown-label">Opening ceremony countdown</span>
<div class="countdown-units"><span><b data-unit="days">--</b><small>Days</small></span><i>:</i><span><b data-unit="hours">--</b><small>Hours</small></span><i>:</i><span><b data-unit="minutes">--</b><small>Minutes</small></span></div>
</div>
</div>
</div>
<div class="medal-podium" aria-label="Top three delegations">
<?php if (!$hasMedals): ?>
<div class="podium-placeholder">
<?= medal_icon('gold',46) ?>
<strong>Medal standings begin<br>December 5, 2026</strong>
<span>Verified results will appear here as events conclude.</span>
</div>
<?php else: foreach ([[1,'silver','2nd'],[0,'gold','1st'],[2,'bronze','3rd']] as [$idx,$tier,$place]):
    $t=$leaders[$idx]??null; ?>
<div class="podium <?= $tier ?>">
<div class="podium-top">
<?= medal_icon($tier,38) ?>
<span class="podium-team"><?= $t?htmlspecialchars($t['team']):'To be decided' ?></span>
</div>
<div class="podium-step">
<b><?= $idx+1 ?></b>
<small><?= $t?((int)$t['gold'].' Gold · '.(int)$t['silver'].' Silver · '.(int)$t['bronze'].' Bronze'):'—' ?></small>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</section>
<section>
<div class="section-heading">
<div><h2 class="section-title">Official Medal Table</h2><p class="tally-note">Ranked by Gold, then Silver, then Bronze · Final / verified events only<?= $tallyUpdatedAt ? ' · Updated ' . htmlspecialchars(date('M j, g:i A', strtotime($tallyUpdatedAt))) : '' ?></p></div>
<a href="<?= app_url('results.php') ?>">View all results <span class="arr"><?= icon("arrow",14) ?></span></a>
</div>
<div class="table-card leaderboard">
<table class="score-table">
<thead>
<tr>
<th>Rank</th>
<th>Delegation</th>
<th class="c"><?= medal_icon('gold',22) ?><span>Gold</span></th>
<th class="c"><?= medal_icon('silver',22) ?><span>Silver</span></th>
<th class="c"><?= medal_icon('bronze',22) ?><span>Bronze</span></th>
<th class="c">Total</th>
</tr>
</thead>
<tbody>
<?php if (!$leaders): ?><tr>
<td colspan="6" class="empty">No medals have been published yet.</td>
</tr>
<?php endif;
$rank=0;
foreach($leaders as $i=>$team):
    $total=$team['gold']+$team['silver']+$team['bronze'];
    $rank=$i+1;
    $tier=$rank <= 3 ? ['','gold','silver','bronze'][$rank] : ''; ?><tr class="row <?= area_brand_class($team['team']) ?> <?= $tier ? 'top top-'.$tier : '' ?>" style="--i:<?= $i ?>">
<td class="rank"><span class="rank-badge <?= $tier ?>"><?= $rank ?></span></td>
<td class="team">
<?= area_logo_bg($team['team']) ?>
<strong><?= htmlspecialchars($team['team']) ?></strong>
</td>
<td class="c"><span class="count c-gold" data-count="<?= (int)$team['gold'] ?>"><?= (int)$team['gold'] ?></span></td>
<td class="c"><span class="count c-silver" data-count="<?= (int)$team['silver'] ?>"><?= (int)$team['silver'] ?></span></td>
<td class="c"><span class="count c-bronze" data-count="<?= (int)$team['bronze'] ?>"><?= (int)$team['bronze'] ?></span></td>
<td class="c total"><strong data-count="<?= $total ?>"><?= $total ?></strong></td>
</tr>
<?php endforeach;
?></tbody>
</table>
</div>
</section>
<section style="margin-top:42px">
<div class="section-heading">
<h2 class="section-title">Sports Desk</h2>
<a href="<?= app_url('results.php') ?>">Explore sports <span class="arr"><?= icon("arrow",14) ?></span></a>
</div>
<div class="grid">
<?php foreach($sports as $sport): $venue = sport_venue($sport['name']); ?><a class="sport-card" href="<?= app_url('sports.php') ?>">
<span class="sport-icon"><?= sport_icon($sport['name'],$sport['icon'],30) ?></span>
<h3>
<?= htmlspecialchars($sport['name']) ?></h3>
<p>
<?= htmlspecialchars($venue ?: ($sport['description'] ?: 'Venue to be announced.')) ?></p>
</a>
<?php endforeach;
?></div>
</section>
<?php page_foot();
?>
