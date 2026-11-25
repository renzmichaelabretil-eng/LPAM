<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/sport_details.php';

$sports = db()->query('SELECT name, icon FROM sports WHERE active=1 ORDER BY name')->fetchAll();
$officialFacebookUrl = 'https://www.facebook.com/profile.php?id=61595577644602';

page_head('About the Meet', 'about');
?>
<div class="admin-hero about-hero"><div>
    <span class="eyebrow">DepEd Leyte Division · Jaro, Leyte</span>
    <h1>About the Meet</h1>
    <p class="admin-subtitle">The official information hub for the 2026 Leyte Provincial Athletic Meet, including verified medal standings, sports schedules, venues, and results.</p>
</div></div>

<section class="about-grid" aria-label="Meet information">
    <article class="about-card">
        <span class="about-icon" aria-hidden="true">f</span>
        <h2>Official Facebook Page</h2>
        <p>Follow the Provincial Athletic Meet’s official Facebook page for public announcements, photos, and event updates.</p>
        <a class="btn" href="<?= htmlspecialchars($officialFacebookUrl) ?>" target="_blank" rel="noopener noreferrer">Visit official Facebook page</a>
    </article>
    <article class="about-card developer-card">
        <span class="about-icon" aria-hidden="true">i</span>
        <h2>System Development Team</h2>
        <p>This Medal Tracker System supports verified medal standings and competition results for the DepEd Leyte Division Athletic Meet 2026.</p>
        <div class="developer-chart" aria-label="System development organizational chart">
            <div class="org-node org-lead"><b>Project Sponsor / Organizing Committee Chair</b><span>Raphi T. Anover · Leyte Provincial Athletic Meet 2026</span></div>
            <div class="org-branches">
                <div class="org-node"><b>Technical Adviser</b><div class="org-people"><figure><img src="<?= app_url('assets/team-mary-rose.png') ?>" alt="Mary Rose L. Bollido"><figcaption>Mary Rose L. Bollido<small>OIC ITO / AO II</small></figcaption></figure></div></div>
                <div class="org-node"><b>Lead Developers</b><div class="org-people"><figure><img src="<?= app_url('assets/team-bradley.png') ?>" alt="Bradley Mark M. Gerarcas"><figcaption>Bradley Mark M. Gerarcas<small>PDO I</small></figcaption></figure><figure><img src="<?= app_url('assets/team-renz.png') ?>" alt="Renz Michael C. Abretil"><figcaption>Renz Michael C. Abretil<small>PDO I</small></figcaption></figure></div></div>
                <div class="org-node"><b>Quality Assurance Testers</b><div class="org-people"><figure><img src="<?= app_url('assets/team-rojie.png') ?>" alt="Rojie B. Camiller"><figcaption>Rojie B. Camiller<small>AA I</small></figcaption></figure><figure><img src="<?= app_url('assets/team-ceasar.png') ?>" alt="Ceasar Jay Cayanong"><figcaption>Ceasar Jay Cayanong<small>AO II</small></figcaption></figure></div></div>
            </div>
        </div>
    </article>
</section>

<section class="venue-directory" aria-labelledby="venue-directory-title">
    <div class="directory-heading">
        <div><span class="eyebrow">Sports event venues</span><h2 id="venue-directory-title">Maps by Sport</h2></div>
        <p>Choose a sport to open its venue in Google Maps.</p>
    </div>
    <div class="venue-map-grid">
        <?php foreach ($sports as $sport):
            $venue = sport_venue($sport['name']) ?: 'Venue to be announced';
            $hasVenue = $venue !== 'Venue to be announced';
            $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($venue . ', Leyte, Philippines');
        ?>
        <article class="venue-map-card">
            <span class="sport-icon"><?= sport_icon($sport['name'], $sport['icon'], 24) ?></span>
            <div><h3><?= htmlspecialchars($sport['name']) ?></h3><p><?= htmlspecialchars($venue) ?></p></div>
            <?php if ($hasVenue): ?><a href="<?= htmlspecialchars($mapUrl) ?>" target="_blank" rel="noopener noreferrer">Open map ↗</a><?php else: ?><span class="map-tba">Map to follow</span><?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php page_foot(); ?>
