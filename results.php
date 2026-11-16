<?php require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/medals.php';
require_once __DIR__ . '/includes/layout.php';
$pdo=db();
$sports=$pdo->query('SELECT * FROM sports WHERE active=1 ORDER BY name')->fetchAll();
$id=filter_input(INPUT_GET,'sport',FILTER_VALIDATE_INT);
$sql='SELECT e.*,s.name sport_name,s.icon,r.gold_team,r.silver_team,r.bronze_team,r.gold_player,r.silver_player,r.bronze_player,r.gold_photo,r.silver_photo,r.bronze_photo FROM events e JOIN sports s ON s.id=e.sport_id LEFT JOIN results r ON r.event_id=e.id';
$params=[];
if($id) {
    $sql.=' WHERE e.sport_id=?';
    $params[]=$id;
}
$sql.=' ORDER BY e.scheduled_at DESC,e.name';
$stmt=$pdo->prepare($sql);
$stmt->execute($params);
$events=$stmt->fetchAll();
$selectedSport=null;
foreach ($sports as $sport) { if ((int)$sport['id'] === $id) { $selectedSport=$sport; break; } }
$sportLeaders=$selectedSport ? medal_standings($id) : [];
page_head('Event Results','results');
?>
<div class="admin-hero">
<div>
<span class="eyebrow">Verified competition results</span>
<h1>Event Results</h1>
</div>
</div>
<div class="filters">
<a class="filter
<?= !$id?'selected':'' ?>" href="<?= app_url('results.php') ?>">All sports</a>
<?php foreach($sports as $sport): ?><a class="filter
<?= $id===$sport['id']?'selected':'' ?>" href="<?= app_url('results.php') ?>?sport=<?= $sport['id'] ?>">
<?= sport_icon($sport['name'],$sport['icon'],16) ?><?= htmlspecialchars($sport['name']) ?></a>
<?php endforeach;
?></div>
<?php if ($selectedSport): ?>
<section class="sport-leaderboard">
<div class="section-heading"><div><h2 class="section-title"><?= sport_icon($selectedSport['name'],$selectedSport['icon'],24) ?> <?= htmlspecialchars($selectedSport['name']) ?> Medal Standing</h2><p class="tally-note">Final / verified events only</p></div></div>
<div class="table-card leaderboard"><table class="score-table"><thead><tr><th>Rank</th><th>Area</th><th class="c"><?= medal_icon('gold',22) ?><span>Gold</span></th><th class="c"><?= medal_icon('silver',22) ?><span>Silver</span></th><th class="c"><?= medal_icon('bronze',22) ?><span>Bronze</span></th><th class="c">Total</th></tr></thead><tbody>
<?php $rank=0; $lastScore=null; foreach($sportLeaders as $i=>$team): $total=(int)$team['gold']+(int)$team['silver']+(int)$team['bronze']; $score=$team['gold'].'-'.$team['silver'].'-'.$team['bronze']; if($score!==$lastScore){$rank=$i+1;$lastScore=$score;} $tier=$rank<=3?['','gold','silver','bronze'][$rank]:''; ?><tr><td class="rank"><span class="rank-badge <?= $tier ?>"><?= $rank ?></span></td><td class="team"><?= area_logo_bg($team['team']) ?><strong><?= htmlspecialchars($team['team']) ?></strong></td><td class="c"><span class="count c-gold"><?= (int)$team['gold'] ?></span></td><td class="c"><span class="count c-silver"><?= (int)$team['silver'] ?></span></td><td class="c"><span class="count c-bronze"><?= (int)$team['bronze'] ?></span></td><td class="c total"><strong><?= $total ?></strong></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>
<?php endif; ?>
<div class="updates">
<?php if(!$events): ?><div class="update-card">No events match this selection.</div>
<?php endif;
foreach($events as $e): ?><article class="update-card event-card">
<span class="status
<?= $e['status'] ?>">
<?= $e['status'] ?></span>
<h3>
<span class="title-ic"><?= sport_icon($e['sport_name'],$e['icon'],20) ?></span><?= htmlspecialchars($e['name']) ?></h3>
<small>
<?= htmlspecialchars($e['sport_name']) ?>·
<?= htmlspecialchars($e['division']) ?>·
<?= $e['scheduled_at'] ? date('M j, g:i A',strtotime($e['scheduled_at'])) : 'Schedule TBA' ?><?= $e['venue'] ? ' · ' . htmlspecialchars($e['venue']) : '' ?></small>
<?php if($e['gold_team']): ?><div class="winners">
<span class="winner">
<?php if ($e['gold_photo']): ?><img class="athlete-photo" src="<?= app_url($e['gold_photo']) ?>" alt="<?= htmlspecialchars($e['gold_player'] ?: $e['gold_team']) ?>"><?php endif; ?>
<strong><?= medal_icon('gold',20) ?> Gold</strong>
<?= htmlspecialchars($e['gold_team']) ?><?= $e['gold_player'] ? ' · ' . htmlspecialchars($e['gold_player']) : '' ?></span>
<span class="winner">
<?php if ($e['silver_photo']): ?><img class="athlete-photo" src="<?= app_url($e['silver_photo']) ?>" alt="<?= htmlspecialchars($e['silver_player'] ?: $e['silver_team']) ?>"><?php endif; ?>
<strong><?= medal_icon('silver',20) ?> Silver</strong>
<?= htmlspecialchars($e['silver_team']) ?><?= $e['silver_player'] ? ' · ' . htmlspecialchars($e['silver_player']) : '' ?></span>
<span class="winner">
<?php if ($e['bronze_photo']): ?><img class="athlete-photo" src="<?= app_url($e['bronze_photo']) ?>" alt="<?= htmlspecialchars($e['bronze_player'] ?: $e['bronze_team']) ?>"><?php endif; ?>
<strong><?= medal_icon('bronze',20) ?> Bronze</strong>
<?= htmlspecialchars($e['bronze_team']) ?><?= $e['bronze_player'] ? ' · ' . htmlspecialchars($e['bronze_player']) : '' ?></span>
</div>
<?php else: ?><div class="winners">
<span class="winner">Results pending verification</span>
</div>
<?php endif;
?></article>
<?php endforeach;
?></div>
<?php page_foot();
?>
