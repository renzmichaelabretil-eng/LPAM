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
<div class="content-tools" data-content-controls data-target="#results-list" data-item=".event-card" data-page-size="8"><label class="content-search"><span>Search results</span><input type="search" placeholder="Event, sport, athlete, or area"></label><button type="button" class="btn" data-search-button>Search</button><button type="button" class="btn secondary" data-list-toggle data-label="results" aria-expanded="true">Hide results</button><p class="content-count" data-content-count></p></div>
<?php if ($selectedSport): ?>
<section class="sport-leaderboard">
<div class="section-heading"><div><h2 class="section-title"><?= sport_icon($selectedSport['name'],$selectedSport['icon'],24) ?> <?= htmlspecialchars($selectedSport['name']) ?> Medal Standing</h2><p class="tally-note">Final / verified events only</p></div></div>
<div class="table-card leaderboard"><table class="score-table"><thead><tr><th>Rank</th><th>Area</th><th class="c"><?= medal_icon('gold',22) ?><span>Gold</span></th><th class="c"><?= medal_icon('silver',22) ?><span>Silver</span></th><th class="c"><?= medal_icon('bronze',22) ?><span>Bronze</span></th><th class="c">Total</th></tr></thead><tbody>
<?php foreach($sportLeaders as $i=>$team): $rank=$i+1; $total=(int)$team['gold']+(int)$team['silver']+(int)$team['bronze']; $tier=$rank<=3?['','gold','silver','bronze'][$rank]:''; ?><tr class="<?= area_brand_class($team['team']) ?>"><td class="rank"><span class="rank-badge <?= $tier ?>"><?= $rank ?></span></td><td class="team"><?= area_logo_bg($team['team']) ?><strong><?= htmlspecialchars($team['team']) ?></strong></td><td class="c"><span class="count c-gold"><?= (int)$team['gold'] ?></span></td><td class="c"><span class="count c-silver"><?= (int)$team['silver'] ?></span></td><td class="c"><span class="count c-bronze"><?= (int)$team['bronze'] ?></span></td><td class="c total"><strong><?= $total ?></strong></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>
<?php endif; ?>
<div class="updates" id="results-list">
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
<?php foreach (['gold' => 'Gold', 'silver' => 'Silver', 'bronze' => 'Bronze'] as $medal => $label): $team = $e[$medal . '_team']; $player = $e[$medal . '_player']; $photo = $e[$medal . '_photo']; ?>
<span class="winner">
<?php if ($photo): ?><button class="athlete-photo-trigger" type="button" data-result-photo data-image="<?= htmlspecialchars(app_url($photo), ENT_QUOTES) ?>" data-player="<?= htmlspecialchars($player ?: $team, ENT_QUOTES) ?>" data-team="<?= htmlspecialchars($team, ENT_QUOTES) ?>" data-medal="<?= $label ?>" data-event="<?= htmlspecialchars($e['name'], ENT_QUOTES) ?>" data-sport="<?= htmlspecialchars($e['sport_name'], ENT_QUOTES) ?>" data-venue="<?= htmlspecialchars($e['venue'] ?: 'Venue to be announced', ENT_QUOTES) ?>" aria-label="View <?= htmlspecialchars($player ?: $team) ?> details"><img class="athlete-photo" src="<?= app_url($photo) ?>" alt=""></button><?php endif; ?>
<strong><?= medal_icon($medal,20) ?> <?= $label ?></strong>
<?= htmlspecialchars($team) ?><?= $player ? ' · ' . htmlspecialchars($player) : '' ?></span>
<?php endforeach; ?>
</div>
<?php else: ?><div class="winners">
<span class="winner">Results pending verification</span>
</div>
<?php endif;
?></article>
<?php endforeach;
?></div>
<dialog class="result-modal" id="result-photo-modal" aria-labelledby="result-modal-title">
<section class="result-modal-card">
<button class="result-modal-close" type="button" data-modal-close aria-label="Close winner details">×</button>
<img id="result-modal-image" class="result-modal-image" src="" alt="">
<div class="result-modal-copy"><span id="result-modal-medal" class="result-modal-medal"></span><h2 id="result-modal-title"></h2><p id="result-modal-team"></p></div>
<dl class="result-modal-details"><div><dt>Event</dt><dd id="result-modal-event"></dd></div><div><dt>Sport</dt><dd id="result-modal-sport"></dd></div><div><dt>Venue</dt><dd id="result-modal-venue"></dd></div></dl>
<button class="btn result-modal-done" type="button" data-modal-close>Close details</button>
</section>
</dialog>
<?php page_foot();
?>
