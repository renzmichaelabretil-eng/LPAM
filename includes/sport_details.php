<?php

/** Official competition venues supplied by the organizing committee. */
function sport_venue(string $sportName): ?string
{
    $venues = [
        'archery' => 'BGCNHS, Brgy. Uguiao, Jaro, Leyte',
        'arnis' => 'Macopa Covered Court, Brgy. Macopa, Jaro, Leyte',
        'athleticstrack' => 'LSA, Tacloban City',
        'badminton' => 'Evacuation Center, Brgy. Sto. Niño',
        'baseball' => 'Capoocan Central School, Capoocan, Leyte',
        // icon_key() deliberately removes digits, so 5x5 becomes x and 3-on-3 becomes on.
        'basketballxsec' => 'Jaro Sports Complex',
        'basketballxelem' => 'Brgy. 4 Covered Court',
        'basketballonsecondarygirlsdaro' => 'Daro Covered Court',
        'billiards' => 'To be played where the table is rented',
        'billards' => 'To be played where the table is rented',
        'boxing' => 'Public Market',
        'chess' => 'ABC Hall Building and SB Legislative Building',
        'dancesports' => 'Jaro Sports Complex',
        'futsal' => 'Kalinawan Covered Court',
        'footballelem' => 'Jaro Grand Stand / Tunga Central School',
        'gymnasticswag' => 'Sta. Cruz Covered Court',
        'paragames' => 'Alahag Covered Court',
        'pencaksilat' => 'GKNHS Covered Court',
        'sepaktakrawsec' => 'GKNHS and Hiagsam Covered Court',
        'softball' => 'Jaro Grand Stand',
        'swimming' => 'LSA, Tacloban City',
        'tabletennis' => 'St. Matthew Parish Hall',
        'tennis' => 'Jaro I Central School',
        'taekwondo' => 'Jaro Cultural Center',
        'volleyball' => 'Bagsakan HS (M), Olotan Covered Court HS (F), Malobago Elementary (G), Sto. Niño Covered Court Elementary (B)',
        'wrestling' => 'LSA, Tacloban City',
        'wushu' => 'Hibucawan Covered Court',
        'weightlifting' => 'Hibucawan Elementary School',
    ];

    $key = icon_key($sportName);
    if (isset($venues[$key])) {
        return $venues[$key];
    }
    foreach ($venues as $sportKey => $venue) {
        if (str_starts_with($key, $sportKey)) {
            return $venue;
        }
    }
    return null;
}
