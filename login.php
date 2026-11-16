<?php require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
if(is_admin()) {
    header('Location: admin/');
    exit;
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $stmt=db()->prepare('SELECT * FROM admins WHERE email=? AND active=1');
    $stmt->execute([strtolower(trim($_POST['email']??''))]);
    $admin=$stmt->fetch();
    if($admin&&password_verify($_POST['password']??'',$admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin']=$admin;
        header('Location: '.((int)($admin['must_change_password'] ?? 0) === 1 ? 'admin/change_password.php' : 'admin/'));
        exit;
    }
    $error='Incorrect email or password.';
}
require_once __DIR__ . '/includes/layout.php';
page_head('Admin Sign In');
?><div class="auth-wrap">
<form class="form-card" method="post"><?= csrf_field() ?>
<h1>Admin sign in</h1>
<p>For authorized sport and meet administrators only.</p>
<?php if($error): ?><div class="notice error">
<?= $error ?></div>
<?php endif;
?><div class="field">
<label>Email address</label>
<input required type="email" name="email" autocomplete="email">
</div>
<div class="field">
<label>Password</label>
<input required type="password" name="password" autocomplete="current-password">
</div>
<button class="btn" type="submit">Sign in to console</button>
</form>
</div>
<?php page_foot();
?>
