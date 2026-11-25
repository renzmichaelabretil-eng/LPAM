<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/sport_details.php';
$sports=db()->query('SELECT s.*,COUNT(e.id) AS event_count FROM sports s LEFT JOIN events e ON e.sport_id=s.id WHERE s.active=1 GROUP BY s.id ORDER BY s.name')->fetchAll();
$events=db()->query('SELECT e.*,s.name sport_name,s.icon FROM events e JOIN sports s ON s.id=e.sport_id WHERE s.active=1 ORDER BY s.name,e.scheduled_at IS NULL,e.scheduled_at,e.name')->fetchAll();
$eventsBySport=[]; foreach($events as $event){$eventsBySport[$event['sport_id']][]=$event;}
page_head('Sports Details','sports');
?>
<div class="admin-hero"><div><span class="eyebrow">Leyte Games 2026</span><h1>Sports Details</h1><p class="admin-subtitle">Competition venues and schedules for every official sport.</p></div></div>
<div class="content-tools" data-content-controls data-target="#sports-list" data-item=".sport-detail-card" data-page-size="12"><label class="content-search"><span>Search sports</span><input type="search" placeholder="Sport or venue"></label><button type="button" class="btn" data-search-button>Search</button><button type="button" class="btn secondary" data-list-toggle data-label="sports" aria-expanded="true">Hide sports</button><p class="content-count" data-content-count></p></div>
<div class="sports-details-grid" id="sports-list"><?php foreach($sports as $sport): $venue = sport_venue($sport['name']) ?: 'Venue to be announced.'; ?><article class="sport-detail-card"><div class="sport-detail-heading"><span class="sport-icon"><?= sport_icon($sport['name'],$sport['icon'],26) ?></span><div><h2><?= htmlspecialchars($sport['name']) ?></h2><p><?= htmlspecialchars($sport['description'] ?? '') ?></p></div></div><p class="sport-venue"><b>Venue</b><?= htmlspecialchars($venue) ?></p><span class="event-total"><?= (int)$sport['event_count'] ?> event<?= $sport['event_count'] == 1 ? '' : 's' ?></span><?php if(empty($eventsBySport[$sport['id']])): ?><p class="no-events">Schedule to be announced.</p><?php else: ?><div class="sport-event-list"><?php foreach($eventsBySport[$sport['id']] as $event): ?><div><strong><?= htmlspecialchars($event['name']) ?></strong><span><?= htmlspecialchars($event['division'] ?: 'Open division') ?><?= $event['venue'] ? ' · ' . htmlspecialchars($event['venue']) : ' · Venue TBA' ?><?= $event['scheduled_at'] ? ' · ' . date('M j, g:i A',strtotime($event['scheduled_at'])) : '' ?></span></div><?php endforeach; ?></div><?php endif; ?></article><?php endforeach; ?></div>
<?php page_foot(); ?>
