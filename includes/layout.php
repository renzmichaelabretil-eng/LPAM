<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

function app_url(string $path = '/'): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    // Admin pages live one directory deeper, but every link belongs at the app root.
    $base = str_contains($script, '/admin/') ? dirname(dirname($script)) : dirname($script);

    return rtrim($base, '/.') . '/' . ltrim($path, '/');
}

function page_head(string $title, string $active = ''): void
{
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $adminPage = basename($scriptPath);
    $isAdminRoute = str_contains($scriptPath, '/admin/');
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> | Leyte Provincial Athletic Meet</title>
    <link rel="stylesheet" href="<?= app_url('assets/style.css') ?>?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?: 1 ?>">
</head>
<body>
    <?= medal_defs() ?>
    <div class="bg-lights" aria-hidden="true"><span class="beam b1"></span><span class="beam b2"></span><span class="beam b3"></span><span class="orb o1"></span><span class="orb o2"></span></div>
    <header class="site-header">
        <a class="brand" href="<?= app_url() ?>">
            <img class="brand-logo" src="<?= app_url('assets/leyte-meet-logo-web.png') ?>" alt="DepEd Leyte Division Athletic Meet logo">
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" aria-label="Open navigation menu"><span></span><span></span><span></span></button>
        <nav id="site-navigation" class="<?= is_admin() ? 'admin-header-nav' : '' ?>">
            <?php if (is_admin()): ?>
                <a href="<?= app_url() ?>">Public portal ↗</a>
                <?php if (is_sports_admin() || is_technical_official()): ?><a class="<?= $isAdminRoute && ($adminPage === 'index.php' || str_ends_with($scriptPath, '/admin/')) ? 'active' : '' ?>" href="<?= app_url('admin/') ?>">Results desk</a><?php endif; ?>
                <?php if (is_sports_admin()): ?><a class="<?= $isAdminRoute && $adminPage === 'sports.php' ? 'active' : '' ?>" href="<?= app_url('admin/sports.php') ?>">Sports &amp; Events</a><a class="<?= $isAdminRoute && $adminPage === 'entry_review.php' ? 'active' : '' ?>" href="<?= app_url('admin/entry_review.php') ?>">Entry approvals</a><?php endif; ?>
                <?php if (is_area_coordinator()): ?><a class="<?= $isAdminRoute && $adminPage === 'entries.php' ? 'active' : '' ?>" href="<?= app_url('admin/entries.php') ?>">My entries</a><?php endif; ?>
                <?php if (is_super_admin()): ?>
                    <a class="<?= $isAdminRoute && $adminPage === 'areas.php' ? 'active' : '' ?>" href="<?= app_url('admin/areas.php') ?>">Areas</a>
                    <a class="<?= $isAdminRoute && $adminPage === 'users.php' ? 'active' : '' ?>" href="<?= app_url('admin/users.php') ?>">Administrators</a>
                    <a class="<?= $isAdminRoute && $adminPage === 'audit.php' ? 'active' : '' ?>" href="<?= app_url('admin/audit.php') ?>">Audit log</a>
                    <a class="<?= $isAdminRoute && $adminPage === 'update.php' ? 'active' : '' ?>" href="<?= app_url('admin/update.php') ?>">Announcements</a>
                <?php endif; ?>
                <a class="login" href="<?= app_url('logout.php') ?>">Sign out</a>
            <?php else: ?>
                <a class="<?= $active === 'home' ? 'active' : '' ?>" href="<?= app_url() ?>">Medal Tracker</a>
                <a class="<?= $active === 'results' ? 'active' : '' ?>" href="<?= app_url('results.php') ?>">Results</a>
                <a class="<?= $active === 'sports' ? 'active' : '' ?>" href="<?= app_url('sports.php') ?>">Sports Details</a>
                <a class="<?= $active === 'calendar' ? 'active' : '' ?>" href="<?= app_url('calendar.php') ?>">Calendar</a>
                <a class="<?= $active === 'about' ? 'active' : '' ?>" href="<?= app_url('updates.php') ?>">About</a>
                <a class="login" href="<?= app_url('login.php') ?>">Admin sign in</a>
            <?php endif; ?>
        </nav>
    </header>
    <div class="top-pattern" aria-hidden="true"></div>
    <main>
    <?php
}

function page_foot(): void
{
    ?>
    </main>
    <div class="bottom-pattern" aria-hidden="true"></div>
    <footer>
        <img class="footer-deped-logo" src="<?= app_url('assets/footer-deped-logo.png') ?>" alt="Department of Education, Bagong Pilipinas, and Schools Division of Leyte">
        <p class="footer-copyright">© 2026 DepEd Leyte Division Athletic Meet Medal Tracker System</p>
        <img class="footer-meet-logo" src="<?= app_url('assets/footer-meet-logo.png') ?>" alt="Leyte Provincial Athletic Meet 2026">
    </footer>
    <script>window.csrfToken=<?= json_encode(csrf_token()) ?>;</script>
    <script src="<?= app_url('assets/app.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/app.js') ?: 1 ?>"></script>
</body>
</html>
    <?php
}

function admin_nav(string $active): void
{
    // Admin navigation is intentionally rendered only in the main site header.
}
