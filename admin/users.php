<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity.php';
require_super_admin();

$pdo = db();
$currentAdmin = current_admin();
$sports = $pdo->query('SELECT id, name FROM sports WHERE active=1 ORDER BY name')->fetchAll();
$areas = $pdo->query('SELECT id, name FROM delegations WHERE active=1 ORDER BY name')->fetchAll();
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editing = null;
if ($editId) { $stmt=$pdo->prepare('SELECT id,name,email,role,sport_id FROM admins WHERE id=?'); $stmt->execute([$editId]); $editing=$stmt->fetch(); }

function administrator_input(PDO $pdo, ?int $excludeId = null): array {
    $name=trim($_POST['name'] ?? '');
    $email=strtolower(trim($_POST['email'] ?? ''));
    $role=$_POST['role'] ?? 'sport_admin';
    $sportId=filter_input(INPUT_POST, 'sport_id', FILTER_VALIDATE_INT);
    $areaId=filter_input(INPUT_POST, 'area_id', FILTER_VALIDATE_INT);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['super_admin','sport_admin','area_coordinator','technical_official'], true)) throw new RuntimeException('Enter a name, valid email, and valid access role.');
    if ($role === 'sport_admin' && !$sportId) throw new RuntimeException('Select the sport this administrator may manage.');
    if ($role === 'area_coordinator' && !$areaId) throw new RuntimeException('Select the area this coordinator may manage.');
    $emailCheck=$pdo->prepare('SELECT COUNT(*) FROM admins WHERE email=? AND id<>?'); $emailCheck->execute([$email,$excludeId ?: 0]);
    if ($emailCheck->fetchColumn()) throw new RuntimeException('That email address is already assigned to an administrator.');
    if ($role === 'sport_admin') { $sportCheck=$pdo->prepare("SELECT COUNT(*) FROM admins WHERE role='sport_admin' AND sport_id=? AND id<>?"); $sportCheck->execute([$sportId,$excludeId ?: 0]); if ($sportCheck->fetchColumn()) throw new RuntimeException('That sport already has an assigned Sport Administrator.'); }
    return [$name,$email,$role,in_array($role,['sport_admin','technical_official'],true) ? $sportId : null,$role === 'area_coordinator' ? $areaId : null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $action=$_POST['action'] ?? '';
        if ($action === 'delete') {
            $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Administrator not found.');
            if ($id === (int)$currentAdmin['id']) throw new RuntimeException('You cannot delete your own account.');
            $stmt=$pdo->prepare('SELECT role FROM admins WHERE id=?'); $stmt->execute([$id]); $target=$stmt->fetch();
            if (!$target) throw new RuntimeException('Administrator not found.');
            if ($target['role'] === 'super_admin' && (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role='super_admin'")->fetchColumn() <= 1) throw new RuntimeException('At least one Meet Administrator account must remain.');
            $pdo->prepare('DELETE FROM admins WHERE id=?')->execute([$id]);
            log_activity('deleted_administrator','administrator',$id,'Deleted an administrator account.');
            flash('success','Administrator account deleted.');
        } elseif ($action === 'create') {
            [$name,$email,$role,$sportId,$areaId]=administrator_input($pdo);
            $password=$_POST['password'] ?? '';
            if (strlen($password) < 10) throw new RuntimeException('Set a temporary password of at least 10 characters.');
            $pdo->prepare('INSERT INTO admins(name,email,password_hash,role,sport_id,area_id,must_change_password) VALUES(?,?,?,?,?,?,1)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$sportId,$areaId]);
            log_activity('created_administrator','administrator',(int)$pdo->lastInsertId(),'Created '.$role.' account for '.$name.'.');
            flash('success','Administrator account created.');
        } elseif ($action === 'update') {
            $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Administrator not found.');
            [$name,$email,$role,$sportId,$areaId]=administrator_input($pdo,$id);
            if ($id === (int)$currentAdmin['id'] && $role !== 'super_admin') throw new RuntimeException('You cannot remove your own Meet Administrator access.');
            $stmt=$pdo->prepare('SELECT role FROM admins WHERE id=?'); $stmt->execute([$id]); $oldRole=$stmt->fetchColumn();
            if ($oldRole === 'super_admin' && $role !== 'super_admin' && (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role='super_admin'")->fetchColumn() <= 1) throw new RuntimeException('At least one Meet Administrator account must remain.');
            $password=$_POST['password'] ?? '';
            if ($password !== '' && strlen($password) < 10) throw new RuntimeException('The new password must have at least 10 characters.');
            if ($password !== '') $pdo->prepare('UPDATE admins SET name=?,email=?,role=?,sport_id=?,area_id=?,password_hash=?,must_change_password=1 WHERE id=?')->execute([$name,$email,$role,$sportId,$areaId,password_hash($password,PASSWORD_DEFAULT),$id]);
            else $pdo->prepare('UPDATE admins SET name=?,email=?,role=?,sport_id=?,area_id=? WHERE id=?')->execute([$name,$email,$role,$sportId,$areaId,$id]);
            log_activity('updated_administrator','administrator',$id,'Updated administrator account for '.$name.'.');
            flash('success','Administrator account updated.');
        }
    } catch (Throwable $error) { flash('error',$error->getMessage()); }
    header('Location: users.php'); exit;
}

$admins=$pdo->query("SELECT a.id,a.name,a.email,a.role,a.sport_id,a.area_id,a.created_at,s.name AS sport_name,d.name AS area_name FROM admins a LEFT JOIN sports s ON s.id=a.sport_id LEFT JOIN delegations d ON d.id=a.area_id ORDER BY CASE a.role WHEN 'super_admin' THEN 0 ELSE 1 END,s.name,a.name")->fetchAll();
$meetAdminCount=count(array_filter($admins, fn($admin) => $admin['role'] === 'super_admin'));
$sportAdminCount=count($admins)-$meetAdminCount;
require_once __DIR__ . '/../includes/layout.php';
page_head('Administrators');
?>
<div class="admin-hero"><div><span class="eyebrow">Access control · Meet Administrator only</span><h1>Administrator Management</h1><p class="admin-subtitle">Manage the people authorized to publish, verify, and administer official meet information.</p></div></div>
<?php admin_nav('administrators'); ?>
<?php if($message=flash('error')): ?><div class="notice error"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if($message=flash('success')): ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="access-summary"><div><b><?= count($admins) ?></b><span>Authorized accounts</span></div><div><b><?= $meetAdminCount ?></b><span>Meet administrators</span></div><div><b><?= $sportAdminCount ?></b><span>Sport administrators</span></div></div>
<section class="account-directory"><div class="directory-heading"><div><span class="form-kicker">Account directory</span><h2>Authorized administrators</h2></div><div class="directory-actions"><p>Meet Administrators have full access. Sport Administrators are limited to one assigned sport.</p><button class="btn" type="button" data-admin-modal="create">+ Add administrator</button></div></div><div class="table-card admin-users-table"><table class="score-table"><thead><tr><th>Administrator</th><th>Role</th><th>Assigned sport</th><th>Created</th><th>Actions</th></tr></thead><tbody><?php foreach($admins as $staff): ?><tr><td class="administrator-cell"><strong><?= htmlspecialchars($staff['name']) ?></strong><small><?= htmlspecialchars($staff['email']) ?></small></td><td><span class="role-badge <?= $staff['role'] ?>"><?= $staff['role']==='super_admin'?'Meet Administrator':'Sport Administrator' ?></span></td><td><?= htmlspecialchars($staff['sport_name'] ?: 'All sports') ?></td><td><?= date('M j, Y', strtotime($staff['created_at'])) ?></td><td class="actions"><button class="btn secondary" type="button" data-admin-modal="edit" data-id="<?= $staff['id'] ?>" data-name="<?= htmlspecialchars($staff['name'], ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($staff['email'], ENT_QUOTES) ?>" data-role="<?= $staff['role'] ?>" data-sport="<?= (int)$staff['sport_id'] ?>">Edit</button><?php if((int)$staff['id'] !== (int)$currentAdmin['id']): ?><button class="btn danger" type="button" data-admin-delete="<?= $staff['id'] ?>" data-name="<?= htmlspecialchars($staff['name'], ENT_QUOTES) ?>">Delete</button><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<dialog class="admin-modal" id="administrator-modal"><form class="modal-card" method="post"><input type="hidden" name="action" id="administrator-action" value="create"><input type="hidden" name="id" id="administrator-id"><div class="modal-heading"><div><span class="form-kicker" id="administrator-kicker">New account</span><h2 id="administrator-title">Create administrator</h2></div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button></div><p id="administrator-help">Create the account, then share the temporary password privately.</p><div class="field"><label>Full name</label><input required id="administrator-name" name="name" maxlength="100"></div><div class="field"><label>Email address</label><input required id="administrator-email" name="email" type="email"></div><div class="field"><label>Access role</label><select required id="administrator-role" name="role"><option value="super_admin">Meet Administrator — all portal settings</option><option value="sport_admin" selected>Sports Administrator — assigned sport events</option><option value="area_coordinator">Area Coordinator — own area entries</option><option value="technical_official">Technical Official — assigned result desk</option></select></div><div class="field"><label>Assigned sport <small>(Sports / Technical roles)</small></label><select id="administrator-sport" name="sport_id"><option value="">No sport assignment</option><?php foreach($sports as $sport): ?><option value="<?= $sport['id'] ?>"><?= htmlspecialchars($sport['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Assigned area <small>(required for Area Coordinator)</small></label><select id="administrator-area" name="area_id"><option value="">No area assignment</option><?php foreach($areas as $area): ?><option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label id="administrator-password-label">Temporary password</label><div class="password-control"><input required id="administrator-password" name="password" type="password" minlength="10" autocomplete="new-password"><button type="button" data-password-toggle="#administrator-password" aria-label="Show password">Show</button></div></div><div class="form-actions"><button class="btn" id="administrator-submit">Create administrator</button><button class="btn secondary" type="button" data-modal-close>Cancel</button></div></form></dialog>
<dialog class="admin-modal confirm-modal" id="delete-administrator-modal"><form class="modal-card" method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete-administrator-id"><div class="modal-heading"><div><span class="form-kicker">Confirm deletion</span><h2>Delete administrator?</h2></div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button></div><p>Remove <strong id="delete-administrator-name"></strong> from the portal? This account will no longer be able to sign in.</p><div class="form-actions"><button class="btn danger">Delete account</button><button class="btn secondary" type="button" data-modal-close>Keep account</button></div></form></dialog>
<?php page_foot(); ?>
