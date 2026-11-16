<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin(true);
$admin=current_admin();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $password=$_POST['password'] ?? '';
    $confirm=$_POST['confirm_password'] ?? '';
    if(strlen($password)<10) $error='Your new password must contain at least 10 characters.';
    elseif($password!==$confirm) $error='The password confirmation does not match.';
    else {
        db()->prepare('UPDATE admins SET password_hash=?,must_change_password=0 WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$admin['id']]);
        $_SESSION['admin']['must_change_password']=0;
        flash('success','Password updated. You can now use the admin console.');
        header('Location: index.php');exit;
    }
}
require_once __DIR__ . '/../includes/layout.php';page_head('Set New Password');
?>
<div class="auth-wrap"><form class="form-card" method="post"><?= csrf_field() ?><span class="eyebrow">Account security</span><h1>Set a new password</h1><p>This temporary password must be replaced before you can access the admin console.</p><?php if($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?><div class="field"><label>New password</label><div class="password-control"><input required id="new-password" name="password" type="password" minlength="10" autocomplete="new-password"><button type="button" data-password-toggle="#new-password">Show</button></div></div><div class="field"><label>Confirm new password</label><div class="password-control"><input required id="confirm-password" name="confirm_password" type="password" minlength="10" autocomplete="new-password"><button type="button" data-password-toggle="#confirm-password">Show</button></div></div><button class="btn">Save new password</button></form></div>
<?php page_foot(); ?>
