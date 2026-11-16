<?php require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/layout.php';
$admin=current_admin();
$pdo=db();
$where='';
$params=[];
if($admin['role']==='sport_admin') {
    $where=' WHERE e.sport_id=?';
    $params[]=$admin['sport_id'];
}
$stmt=$pdo->prepare('SELECT e.*,s.name sport_name,s.icon,r.gold_team FROM events e JOIN sports s ON s.id=e.sport_id LEFT JOIN results r ON r.event_id=e.id'.$where." ORDER BY (e.status='final') ASC, e.scheduled_at ASC, e.name");
$stmt->execute($params);
$events=$stmt->fetchAll();
$pendingCount=count(array_filter($events, fn($event) => $event['status'] !== 'final'));
$verifiedCount=count($events)-$pendingCount;
$deskName=$admin['role']==='sport_admin' ? ($events[0]['sport_name'] ?? 'Assigned sport') . ' Desk' : 'Meet Administration';
page_head('Admin Console');
?>
<div class="admin-hero">
<div>
<span class="eyebrow">Authorized publishing desk</span>
<h1><?= htmlspecialchars($deskName) ?></h1><p class="admin-subtitle">Signed in as <?= htmlspecialchars($admin['name']) ?> · <?= $admin['role']==='sport_admin'?'Sport Administrator':'Meet Administrator' ?></p>
</div>
<?php if($admin['role']==='super_admin'): ?>
<a class="btn" href="<?= app_url('admin/update.php') ?>">+ Publish update</a>
<?php endif; ?>
</div>
<div class="admin-summary"><div><b><?= $pendingCount ?></b><span>Awaiting verification</span></div><div><b><?= $verifiedCount ?></b><span>Final results</span></div><div><b><?= count($events) ?></b><span>Events in your desk</span></div></div>
<?php admin_nav('results'); ?>
<?php if($m=flash('success')): ?><div class="notice success">
<?= htmlspecialchars($m) ?></div>
<?php endif;
?><div class="table-card">
<table class="score-table">
<thead>
<tr>
<th>Sport</th>
<th>Event</th>
<th>Division</th>
<th>Status</th>
<th>Published</th>
<th>
</th>
</tr>
</thead>
<tbody>
<?php foreach($events as $e): ?><tr>
<td>
<span class="title-ic"><?= sport_icon($e['sport_name'],$e['icon'],18) ?></span><?= htmlspecialchars($e['sport_name']) ?></td>
<td>
<strong>
<?= htmlspecialchars($e['name']) ?></strong>
</td>
<td>
<?= htmlspecialchars($e['division']) ?></td>
<td>
<span class="status
<?= $e['status'] ?>">
<?= $e['status'] ?></span>
</td>
<td>
<?= $e['gold_team']?'Yes':'No' ?></td>
<td class="actions">
<a class="btn secondary" href="<?= app_url('admin/event.php') ?>?id=<?= $e['id'] ?>">Enter winners</a>
</td>
</tr>
<?php endforeach;
?></tbody>
</table>
</div>
<?php page_foot();
?>
