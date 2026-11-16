<?php
require_once __DIR__ . '/db.php';

/** The authoritative, active delegation roster for medal entry and standings. */
function delegations(): array
{
    return db()->query('SELECT id, name FROM delegations WHERE active=1 ORDER BY id')->fetchAll();
}

/**
 * Faded background logo for an area row on the leaderboards. Matches names like
 * "Area 2-A", "Area IIA" or "Area 5B" to assets/areas/area-2a.webp; returns '' when no logo exists.
 */
function area_logo_bg(string $name): string
{
    $key = preg_replace('/[^a-z0-9]/', '', preg_replace('/^\s*area\s*/i', '', strtolower($name)));
    if (preg_match('/^(iv|v|i{1,3})([a-z]?)$/', $key, $m)) {
        $key = ['i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5][$m[1]] . $m[2];
    }
    if ($key === '' || !is_file(__DIR__ . "/../assets/areas/area-$key.webp")) return '';
    return '<img class="team-logo" src="' . app_url("assets/areas/area-$key.webp") . '" alt="" aria-hidden="true" loading="lazy">';
}

/**
 * Calculates standings from the source results, so the public tally cannot drift
 * from corrected results. Only competition results marked final are counted.
 */
function medal_standings(?int $sportId = null): array
{
    $sportFilter = $sportId ? ' AND e.sport_id=?' : '';
    $sql = "SELECT d.name AS team,
        COALESCE(m.gold, 0) AS gold, COALESCE(m.silver, 0) AS silver,
        COALESCE(m.bronze, 0) AS bronze
        FROM delegations d
        LEFT JOIN (
            SELECT team, SUM(gold) AS gold, SUM(silver) AS silver, SUM(bronze) AS bronze
            FROM (
                SELECT r.gold_team AS team, 1 AS gold, 0 AS silver, 0 AS bronze
                FROM results r JOIN events e ON e.id=r.event_id
                WHERE e.status='final' AND r.gold_team IS NOT NULL{$sportFilter}
                UNION ALL
                SELECT r.silver_team, 0, 1, 0 FROM results r JOIN events e ON e.id=r.event_id
                WHERE e.status='final' AND r.silver_team IS NOT NULL{$sportFilter}
                UNION ALL
                SELECT r.bronze_team, 0, 0, 1 FROM results r JOIN events e ON e.id=r.event_id
                WHERE e.status='final' AND r.bronze_team IS NOT NULL{$sportFilter}
            ) medals GROUP BY team
        ) m ON m.team=d.name
        WHERE d.active=1
        ORDER BY gold DESC, silver DESC, bronze DESC, d.name";
    $statement = db()->prepare($sql);
    $statement->execute($sportId ? [$sportId, $sportId, $sportId] : []);
    return $statement->fetchAll();
}

function medal_tally_updated_at(): ?string
{
    return db()->query("SELECT MAX(r.updated_at) FROM results r JOIN events e ON e.id=r.event_id WHERE e.status='final'")->fetchColumn() ?: null;
}
