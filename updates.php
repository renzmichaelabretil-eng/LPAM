<?php require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
$updates=db()->query('SELECT u.*,a.name author FROM updates u LEFT JOIN admins a ON a.id=u.author_id ORDER BY published_at DESC')->fetchAll();
page_head('Meet Updates','updates');
?>
<div class="admin-hero">
<div>
<span class="eyebrow">Announcements and verified notices</span>
<h1>Meet Updates</h1>
</div>
</div>
<div class="updates">
<?php foreach($updates as $update): ?><article class="update-card">
<h3>
<?= htmlspecialchars($update['title']) ?></h3>
<p>
<?= nl2br(htmlspecialchars($update['body'])) ?></p>
<time>
<?= date('F j, Y · g:i A',strtotime($update['published_at'])) ?><?= $update['author']?' · '.htmlspecialchars($update['author']):'' ?></time>
</article>
<?php endforeach;
?></div>
<?php page_foot();
?>
