<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity.php';
require_super_admin();
$pdo=db();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $action=$_POST['action']??''; $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
    try {
        if ($action==='save') {
            $name=trim($_POST['name']??''); $coordinator=trim($_POST['coordinator']??''); $districts=trim($_POST['districts']??'');
            if ($name==='') throw new RuntimeException('Area name is required.');
            $check=$pdo->prepare('SELECT COUNT(*) FROM delegations WHERE name=? AND id<>?'); $check->execute([$name,$id?:0]);
            if ($check->fetchColumn()) throw new RuntimeException('That area already exists.');
            if ($id) { $pdo->prepare('UPDATE delegations SET name=?,coordinator=?,districts=? WHERE id=?')->execute([$name,$coordinator?:null,$districts?:null,$id]); log_activity('updated_area','area',$id,'Updated '.$name.'.'); }
            else { $pdo->prepare('INSERT INTO delegations(name,coordinator,districts,active) VALUES(?,?,?,1)')->execute([$name,$coordinator?:null,$districts?:null]); $id=(int)$pdo->lastInsertId(); log_activity('created_area','area',$id,'Added '.$name.'.'); }
            flash('success','Area saved.');
        } elseif ($action==='toggle' && $id) {
            $pdo->prepare('UPDATE delegations SET active=IF(active=1,0,1) WHERE id=?')->execute([$id]); log_activity('toggled_area','area',$id,'Changed area availability.'); flash('success','Area status updated.');
        }
    } catch(Throwable $e) { flash('error',$e->getMessage()); }
    header('Location: areas.php'); exit;
}
$areas=$pdo->query('SELECT d.*, (SELECT COUNT(*) FROM athletes a WHERE a.area_id=d.id) athlete_count, (SELECT COUNT(*) FROM admins a WHERE a.area_id=d.id AND a.active=1) coordinator_accounts FROM delegations d ORDER BY d.active DESC,d.name')->fetchAll();
require_once __DIR__ . '/../includes/layout.php'; page_head('Participating Areas');
?>
<div class="admin-hero"><div><span class="eyebrow">Meet setup · Meet Administrator only</span><h1>Participating Areas</h1><p class="admin-subtitle">Maintain delegations, their coordinators, and the districts or schools they represent.</p></div></div>
<?php if($m=flash('error')):?><div class="notice error"><?= htmlspecialchars($m) ?></div><?php endif; ?><?php if($m=flash('success')):?><div class="notice success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<section class="account-directory"><div class="directory-heading"><div><span class="form-kicker">Delegation directory</span><h2>Areas and coordinators</h2></div><div class="directory-actions"><p>Area 5A and Area 5B are independent delegations. Legacy Area 5 records stay queued for reassignment.</p><button class="btn" type="button" data-area-modal="create">+ Add area</button></div></div><div class="table-card admin-users-table"><table class="score-table"><thead><tr><th>Area</th><th>Coordinator</th><th>Districts / schools</th><th>Contestants</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($areas as $area): ?><tr><td><strong><?= htmlspecialchars($area['name']) ?></strong></td><td><?= htmlspecialchars($area['coordinator']?:'Not assigned') ?><br><small><?= (int)$area['coordinator_accounts'] ?> coordinator account(s)</small></td><td><?= nl2br(htmlspecialchars($area['districts']?:'Not listed')) ?></td><td><?= (int)$area['athlete_count'] ?></td><td><span class="status <?= $area['active']?'final':'upcoming' ?>"><?= $area['active']?'Active':'Inactive' ?></span></td><td class="actions"><button type="button" class="btn secondary" data-area-modal="edit" data-id="<?= $area['id'] ?>" data-name="<?= htmlspecialchars($area['name'],ENT_QUOTES) ?>" data-coordinator="<?= htmlspecialchars($area['coordinator']??'',ENT_QUOTES) ?>" data-districts="<?= htmlspecialchars($area['districts']??'',ENT_QUOTES) ?>">Edit</button><form method="post" class="inline-form"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $area['id'] ?>"><button class="btn secondary"><?= $area['active']?'Deactivate':'Activate' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
<dialog class="admin-modal" id="area-modal"><form class="modal-card" method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="area-id"><div class="modal-heading"><div><span class="form-kicker" id="area-kicker">New delegation</span><h2 id="area-title">Add area</h2></div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button></div><div class="field"><label>Area name</label><input id="area-name" name="name" required maxlength="100" placeholder="e.g. Area 5A"></div><div class="field"><label>Area coordinator</label><input id="area-coordinator" name="coordinator" maxlength="150"></div><div class="field"><label>Districts / schools under this area</label><textarea id="area-districts" name="districts" maxlength="4000" placeholder="One per line or a short list"></textarea></div><div class="form-actions"><button class="btn" id="area-submit">Save area</button><button class="btn secondary" type="button" data-modal-close>Cancel</button></div></form></dialog>
<?php page_foot(); ?>
