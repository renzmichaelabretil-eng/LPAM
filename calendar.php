<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
$pdo=db();
$sports=$pdo->query('SELECT id,name,icon FROM sports WHERE active=1 ORDER BY name')->fetchAll();
$sportId=filter_input(INPUT_GET,'sport',FILTER_VALIDATE_INT);
$sql='SELECT e.*,s.name sport_name,s.icon FROM events e JOIN sports s ON s.id=e.sport_id WHERE s.active=1';
$params=[];
if($sportId){$sql.=' AND e.sport_id=?';$params[]=$sportId;}
$sql.=' ORDER BY e.scheduled_at IS NULL,e.scheduled_at,s.name,e.name';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$events=$stmt->fetchAll();
$dated=[];$tba=[];
foreach($events as $event){if($event['scheduled_at'])$dated[date('Y-m-d',strtotime($event['scheduled_at']))][]=$event;else $tba[]=$event;}
page_head('Event Calendar','calendar');
?>
<div class="calendar-hero"><span class="eyebrow">Leyte Games 2026</span><h1>Event Calendar</h1><p>Official competition schedule, venues, and live event status.</p></div>
<div class="filters"><a class="filter <?= !$sportId?'selected':'' ?>" href="<?= app_url('calendar.php') ?>">All sports</a><?php foreach($sports as $sport): ?><a class="filter <?= $sportId===$sport['id']?'selected':'' ?>" href="<?= app_url('calendar.php') ?>?sport=<?= $sport['id'] ?>"><?= sport_icon($sport['name'],$sport['icon'],16) ?><?= htmlspecialchars($sport['name']) ?></a><?php endforeach; ?></div>
<?php if(!$events): ?><div class="table-card calendar-empty">No events match this selection.</div><?php endif; ?>
<div class="calendar-grid"><?php foreach($dated as $date=>$dayEvents): ?><section class="calendar-day"><header><time datetime="<?= $date ?>"><b><?= date('D',strtotime($date)) ?></b><strong><?= date('j',strtotime($date)) ?></strong><span><?= date('F Y',strtotime($date)) ?></span></time><small><?= count($dayEvents) ?> event<?= count($dayEvents)===1?'':'s' ?></small></header><div class="calendar-events"><?php foreach($dayEvents as $event): ?><article><span class="status <?= $event['status'] ?>"><?= $event['status'] ?></span><div class="calendar-time"><?= date('g:i A',strtotime($event['scheduled_at'])) ?></div><div class="calendar-event-main"><strong><?= sport_icon($event['sport_name'],$event['icon'],16) ?><?= htmlspecialchars($event['name']) ?></strong><span><?= htmlspecialchars($event['sport_name']) ?> · <?= htmlspecialchars($event['division'] ?: 'Open') ?></span><span><?= htmlspecialchars($event['venue'] ?: 'Venue TBA') ?></span></div></article><?php endforeach; ?></div></section><?php endforeach; ?></div>
<?php if($tba): ?><section class="tba-section"><div class="section-heading"><div><h2 class="section-title">Schedule to be announced</h2><p class="tally-note">These events have no confirmed date or time yet.</p></div></div><div class="tba-list"><?php foreach($tba as $event): ?><div><strong><?= sport_icon($event['sport_name'],$event['icon'],16) ?><?= htmlspecialchars($event['name']) ?></strong><span><?= htmlspecialchars($event['sport_name']) ?> · <?= htmlspecialchars($event['division'] ?: 'Open') ?> · <?= htmlspecialchars($event['venue'] ?: 'Venue TBA') ?></span></div><?php endforeach; ?></div></section><?php endif; ?>
<?php page_foot(); ?>
