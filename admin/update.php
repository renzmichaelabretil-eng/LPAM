<?php require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_super_admin();
$admin=current_admin();
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $title=trim($_POST['title']??'');
    $body=trim($_POST['body']??'');
    if($title!==''&&$body!=='') {
        db()->prepare('INSERT INTO updates(title,body,author_id) VALUES(?,?,?)')->execute([$title,$body,$admin['id']]);
        flash('success','Meet update published.');
        header('Location: index.php');
        exit;
    }
    flash('error','Please complete both fields.');
    header('Location: update.php');
    exit;
}
require_once __DIR__ . '/../includes/layout.php';
page_head('Publish Update');
admin_nav('updates');
?><div class="auth-wrap" style="max-width:650px">
<form class="form-card" method="post">
<h1>Publish update</h1>
<p>This announcement will appear immediately in the public updates feed.</p>
<?php if($m=flash('error')): ?><div class="notice error">
<?= htmlspecialchars($m) ?></div>
<?php endif;
?><div class="field">
<label>Headline</label>
<input required name="title" maxlength="180">
</div>
<div class="field">
<label>Announcement</label>
<textarea required name="body">
</textarea>
</div>
<button class="btn">Publish update</button>
<a class="btn secondary" href="/admin/">Cancel</a>
</form>
</div>
<?php page_foot();
?>
