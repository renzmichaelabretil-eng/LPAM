<?php
/**
 * Inline SVG icons. They replace the emoji stored in the database (sports.icon)
 * and the medal emojis, so the look is identical on every device.
 */

const ICON_PATHS = [
    'athletics'    => '<circle cx="14.5" cy="4.5" r="2"/><path d="M12 8.5l-3 2.5-2.5 1M12 8.5l3 3 3 .5M12 8.5l-1.5 5 3.5 3 1 4.5M10.5 13.5l-3 4.5-3 1"/><path d="M3 21h4M17 21h4"/>',
    'basketball'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3v18M5.6 5.6c3 2.5 3 10.3 0 12.8M18.4 5.6c-3 2.5-3 10.3 0 12.8"/>',
    'volleyball'   => '<circle cx="12" cy="12" r="9"/><path d="M12 3c0 5-3 8-8.5 9M12 3c4 2 6 5 5.5 9.5M21 12c-5 0-8.5 3-9.5 8.5M3.8 15c3-1 6-.5 8.2 1.5"/>',
    'badminton'    => '<path d="M9 13l5 5M7 21l2-2M14 18l2-2"/><path d="M6 4l6 10 6-10M8 4h8M9 7h6M10 10h4"/><path d="M12 14v4"/>',
    'taekwondo'    => '<circle cx="8" cy="4.5" r="2"/><path d="M8 7.5l1 6.5-2 7M9 14l11-5M8.5 9L5 11M8.5 9l3.5 2.5"/>',
    'sepaktakraw'  => '<circle cx="12" cy="12" r="9"/><path d="M12 3l4 3v5l-4 3-4-3V6zM16 11l4 2M8 11l-4 2M12 14v7M16 6l1-2.5M8 6L7 3.5"/>',
    'archery'      => '<path d="M5 4c10 4 10 12 0 16M5 4c6 5 6 11 0 16M4 12h16M15 8l5 4-5 4"/><circle cx="7" cy="12" r="1.5"/>',
    'arnis'        => '<path d="M5 4l14 16M19 4L5 20"/><path d="M3.5 5.5l3-3M17.5 21.5l3-3M17.5 2.5l3 3M3.5 18.5l3 3"/>',
    'baseball'     => '<circle cx="17" cy="7" r="3"/><path d="M15 5c1 1 2 2 3 4M16 4c2 1 3 2 4 4M5 19l9-9 3 3-9 9H5z"/>',
    'billiards'    => '<circle cx="8" cy="15" r="4"/><circle cx="17" cy="8" r="3"/><path d="M3 4l16 16M4 5l2-2"/>',
    'boxing'       => '<path d="M8 9V6a2 2 0 014 0v3h3a3 3 0 013 3v4a4 4 0 01-4 4H8a3 3 0 01-3-3v-5a3 3 0 013-3zM8 9h4v5"/>',
    'chess'        => '<path d="M7 21h10M9 18h6M9 18l1-6-3-3 4-5 3 2 3-1-2 5 2 3-3 5z"/><path d="M9 18h6"/>',
    'dancesports'  => '<circle cx="8" cy="5" r="2"/><circle cx="16" cy="6" r="2"/><path d="M8 8l3 4 2-3 3 3M11 12l-4 7M13 12l5 6M13 9l-2-2"/>',
    'football'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7l3 2-1 3h-4L9 9zM5 10l4-1M19 10l-4-1M7 18l3-6M17 18l-3-6"/>',
    'futsal'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7l3 2-1 3h-4L9 9zM5 10l4-1M19 10l-4-1M7 18l3-6M17 18l-3-6"/>',
    'gymnastics'   => '<circle cx="12" cy="5" r="2"/><path d="M12 7l-3 6 3 3 3-3-3-6M5 3v8a3 3 0 006 0V9M19 3v8a3 3 0 01-6 0V9M3 3h4M17 3h4"/>',
    'paragames'    => '<circle cx="10" cy="5" r="2"/><path d="M10 8l3 4h4M13 12l-2 5h5M9 10l-2 5M7 20a7 7 0 107-8"/>',
    'pencaksilat'  => '<circle cx="9" cy="4.5" r="2"/><path d="M9 7l2 6-3 7M10 10l7-3M10 10l-5 3M11 13l6 5"/>',
    'softball'     => '<circle cx="17" cy="7" r="3"/><path d="M15 5c1 1 2 2 3 4M16 4c2 1 3 2 4 4M5 19l9-9 3 3-9 9H5z"/>',
    'swimming'     => '<circle cx="8" cy="7" r="2"/><path d="M10 10l4 2 5-1M3 16c2 2 4 2 6 0 2 2 4 2 6 0 2 2 4 2 6 0M3 20c2 2 4 2 6 0 2 2 4 2 6 0 2 2 4 2 6 0"/>',
    'tabletennis'  => '<path d="M8 4a6 6 0 016 6c0 3-2 5-5 5a6 6 0 01-6-6c0-3 2-5 5-5zM10 15l-3 5"/><circle cx="19" cy="5" r="1.7"/><path d="M15 8l2 1"/>',
    'tennis'       => '<circle cx="12" cy="12" r="9"/><path d="M6 4c3 3 4 5 4 8s-1 5-4 8M18 4c-3 3-4 5-4 8s1 5 4 8"/>',
    'weightlifting'=> '<path d="M3 9v6M6 7v10M18 7v10M21 9v6M6 12h12"/><circle cx="12" cy="5" r="2"/><path d="M12 7v5"/>',
    'wrestling'    => '<circle cx="8" cy="5" r="2"/><circle cx="16" cy="7" r="2"/><path d="M8 8l4 4 3-2 3 3M12 12l-4 7M12 12l5 7M9 10l-4 2"/>',
    'wushu'        => '<circle cx="8" cy="4.5" r="2"/><path d="M8 7l3 6-3 7M10 10l7-4M10 10l-5 4M11 13l7 4"/>',
    'trophy'       => '<path d="M8 4h8v6a4 4 0 01-8 0zM8 6H4v1a4 4 0 004 4M16 6h4v1a4 4 0 01-4 4M12 14v4M8 21h8M9.5 18h5"/>',
    'arrow'        => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'calendar'     => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
];

/** Normalises a sport name so it can be matched, e.g. "Sepak Takraw" -> "sepaktakraw". */
function icon_key(string $name): string
{
    return preg_replace('/[^a-z]/', '', strtolower($name));
}

/** Fallback when a sport name is new: guess from the old emoji saved in the DB. */
function icon_from_emoji(string $emoji): string
{
    $map = [
        '🏃' => 'athletics', '🏀' => 'basketball', '🏐' => 'volleyball',
        '🏸' => 'badminton', '🥋' => 'taekwondo', '⚽' => 'sepaktakraw',
    ];
    return $map[trim($emoji)] ?? 'trophy';
}

function icon(string $name, int $size = 24, string $class = ''): string
{
    $paths = ICON_PATHS[$name] ?? ICON_PATHS['trophy'];
    return '<svg class="ic ' . htmlspecialchars($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

/**
 * Licensed professional sport pictograms from Sporticon (Apache-2.0).
 * These are deliberately not Olympic marks or Olympic pictograms.  They give
 * the portal the same restrained, international-event visual language without
 * implying an Olympic affiliation.  See CREDITS.md for the source.
 */
const SPORTICON_BASE = 'https://raw.githubusercontent.com/ookamiinc/Sporticon/master/src/export/svg/';
const SPORTICON_FILES = [
    'athletics'     => 'track_and_field.svg',
    'badminton'     => 'badminton.svg',
    'baseball'      => 'baseball.svg',
    'basketball'    => 'basketball.svg',
    'dancesports'   => 'dance.svg',
    'football'      => 'soccer.svg',
    'futsal'        => 'futsal.svg',
    'gymnastics'    => 'gymnastics.svg',
    'paragames'     => 'wheelchair_basketball.svg',
    'sepaktakraw'   => 'sepak_takraw.svg',
    'softball'      => 'softball.svg',
    'swimming'      => 'swimming.svg',
    'tabletennis'   => 'table_tennis.svg',
    'tennis'        => 'tennis.svg',
    'volleyball'    => 'volleyball.svg',
    'weightlifting' => 'weightlifting.svg',
    'wrestling'     => 'wrestling.svg',
];

/** Icon for a sport, matched by name first and by the legacy emoji second. */
function sport_icon(string $name, string $emoji = '', int $size = 24): string
{
    $key = icon_key($name);
    // Division labels (for example, "Basketball 5x5 (Sec.)") should still
    // receive their sport's icon instead of the generic trophy fallback.
    foreach ([
        'athletics' => 'athletics', 'basketball' => 'basketball',
        'volleyball' => 'volleyball', 'badminton' => 'badminton',
        'taekwondo' => 'taekwondo', 'sepaktakraw' => 'sepaktakraw',
        'archery' => 'archery', 'arnis' => 'arnis', 'baseball' => 'baseball',
        'softball' => 'softball', 'billiards' => 'billiards', 'billards' => 'billiards',
        'boxing' => 'boxing', 'chess' => 'chess', 'dancesports' => 'dancesports',
        'football' => 'football', 'futsal' => 'futsal', 'gymnastics' => 'gymnastics',
        'paragames' => 'paragames', 'pencaksilat' => 'pencaksilat',
        'swimming' => 'swimming', 'tabletennis' => 'tabletennis', 'tennis' => 'tennis',
        'weightlifting' => 'weightlifting', 'wrestling' => 'wrestling', 'wushu' => 'wushu',
    ] as $prefix => $iconKey) {
        if (str_starts_with($key, $prefix)) {
            $key = $iconKey;
            break;
        }
    }
    if (!isset(ICON_PATHS[$key])) {
        $key = icon_from_emoji($emoji);
    }
    if (isset(SPORTICON_FILES[$key])) {
        $src = SPORTICON_BASE . SPORTICON_FILES[$key];
        return '<img class="sport-pictogram" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
            . '" width="' . $size . '" height="' . $size . '" alt="" aria-hidden="true">';
    }
    return icon($key, $size);
}

/** Official event medal artwork. */
function medal_icon(string $type, int $size = 22): string
{
    $type = in_array($type, ['gold', 'silver', 'bronze'], true) ? $type : 'gold';
    $src = app_url('assets/medal-' . $type . '.png');
    return '<img class="medal-ic medal-ic-' . $type . '" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
        . '" width="' . $size . '" height="' . $size . '" alt="" aria-hidden="true" loading="lazy">';
}

/** Kept for compatibility with the existing layout. */
function medal_defs(): string
{
    return '';
}
