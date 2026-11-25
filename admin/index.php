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
?><section class="result-desk"><div class="directory-heading"><div><span class="form-kicker">Result publishing</span><h2>Events in your desk</h2></div><p>Choose an event to enter, correct, or replace its medal winner photos.</p></div><div class="result-desk-grid"><?php foreach($events as $e): ?><article class="result-desk-card <?= $e['gold_team'] ? 'is-published' : '' ?>"><header><span class="title-ic"><?= sport_icon($e['sport_name'],$e['icon'],22) ?></span><div><small><?= htmlspecialchars($e['sport_name']) ?></small><h3><?= htmlspecialchars($e['name']) ?></h3></div><span class="status <?= $e['status'] ?>"><?= htmlspecialchars($e['status']) ?></span></header><p><?= htmlspecialchars($e['division'] ?: 'Open division') ?><?= $e['venue'] ? ' · ' . htmlspecialchars($e['venue']) : '' ?></p><footer><span class="result-published <?= $e['gold_team'] ? 'yes' : '' ?>"><?= $e['gold_team'] ? '✓ Results published' : '○ Winners not entered' ?></span><a class="btn <?= $e['gold_team'] ? 'secondary' : '' ?>" href="<?= app_url('admin/event.php') ?>?id=<?= $e['id'] ?>"><?= $e['gold_team'] ? 'Edit result' : 'Enter winners' ?></a></footer></article><?php endforeach; ?><?php if(!$events): ?><p class="empty">No events are assigned to this desk.</p><?php endif; ?></div></section>
<?php page_foot();
?>
