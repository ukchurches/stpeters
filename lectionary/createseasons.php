<?php
declare(strict_types=1);

function tz_default(?DateTimeZone $tz = null): DateTimeZone {
    return $tz ?? new DateTimeZone('Europe/London');
}
function ymd(DateTimeInterface $d): string { return $d->format('Y-m-d'); }

function ordinal(int $n): string {
    static $w = [1=>'First',2=>'Second',3=>'Third',4=>'Fourth',5=>'Fifth',6=>'Sixth',7=>'Seventh',8=>'Eighth',9=>'Ninth',10=>'Tenth'];
    return $w[$n] ?? ($n . 'th');
}

function easter_sunday(int $year, ?DateTimeZone $tz = null): DateTimeImmutable {
    $tz = tz_default($tz);
    $daysAfterMarch21 = easter_days($year);
    $base = new DateTimeImmutable(sprintf('%04d-03-21 12:00:00', $year), $tz);
    return $base->modify("+$daysAfterMarch21 days");
}

function advent_sunday(int $year, ?DateTimeZone $tz = null): DateTimeImmutable {
    $tz = tz_default($tz);
    $d = new DateTimeImmutable(sprintf('%04d-11-27 12:00:00', $year), $tz);
    $dow = (int)$d->format('N');         // 1..7
    $add = (7 - $dow) % 7;
    return $d->modify("+$add days");
}

/**
 * Returns rows like:
 * ['date'=>'YYYY-mm-dd','key'=>'...','name'=>'...','type'=>'sunday|principal_feast|principal_holy_day|festival','transferred_from'=>?string]
 */
function liturgical_year_observances_rows(int $litYear, ?DateTimeZone $tz = null): array
{
    $tz = tz_default($tz);

    $start = advent_sunday($litYear - 1, $tz);
    $nextStart = advent_sunday($litYear, $tz);
    $end = $nextStart->modify('-1 day');

    // Anchors
    $advent1   = $start;
    $christmas = new DateTimeImmutable(sprintf('%04d-12-25 12:00:00', $litYear - 1), $tz);
    $epiphany  = new DateTimeImmutable(sprintf('%04d-01-06 12:00:00', $litYear), $tz);

    // Easter-based
    $easter = easter_sunday($litYear, $tz);
    $ashWednesday   = $easter->modify('-46 days');
    $lent1          = $ashWednesday->modify('next sunday');

    $palmSunday     = $easter->modify('-7 days');

    $holyWeekMon    = $easter->modify('-6 days');
    $holyWeekTue    = $easter->modify('-5 days');
    $holyWeekWed    = $easter->modify('-4 days');
    $maundyThursday = $easter->modify('-3 days');
    $goodFriday     = $easter->modify('-2 days');
    $holySaturday   = $easter->modify('-1 day');

    $ascensionDay   = $easter->modify('+39 days');
    $pentecost      = $easter->modify('+49 days');
    $trinitySunday  = $easter->modify('+56 days');

    $christTheKing  = $advent1->modify('-7 days');
    $secondSundayOfEaster = $easter->modify('+7 days');

    // Transfer window + targets
    $transferWindowStart = $palmSunday;
    $transferWindowEnd   = $secondSundayOfEaster;
    $monAfterSecondSundayEaster = $secondSundayOfEaster->modify('+1 day');
    $tueAfterSecondSundayEaster = $secondSundayOfEaster->modify('+2 days');

    $rows = [];
    $addRow = function(DateTimeImmutable $d, string $key, string $name, string $type, ?DateTimeImmutable $from = null)
        use (&$rows, $start, $end)
    {
        if ($d < $start || $d > $end) return;

        $rows[] = [
            'date' => ymd($d),
            'key'  => $key,
            'name' => $name,
            'type' => $type,
            'transferred_from' => $from ? ymd($from) : null,
        ];
    };

    $transfer_if_sunday = fn(DateTimeImmutable $d) =>
        ((int)$d->format('N') === 7) ? $d->modify('+1 day') : $d;

    // -------------------------
    // 1) Sundays (one row each)
    // -------------------------
    // Advent 1–4
    for ($i=1; $i<=4; $i++) {
        $d = $advent1->modify('+' . (7*($i-1)) . ' days');
        $addRow($d, "advent_$i", ordinal($i) . " Sunday of Advent", 'sunday');
    }

    // Christmas Sundays
    $sunAfterChristmas = $christmas->modify('next sunday');
    if ($sunAfterChristmas < $epiphany) $addRow($sunAfterChristmas, 'sunday_after_christmas', 'Sunday after Christmas Day', 'sunday');
    $maybeSecond = $sunAfterChristmas->modify('+7 days');
    if ($sunAfterChristmas < $epiphany && $maybeSecond < $epiphany) $addRow($maybeSecond, 'second_sunday_of_christmas', 'Second Sunday of Christmas', 'sunday');

    // Epiphany to pre-Lent
    $sunAfterEpiphany = $epiphany->modify('next sunday');
    if ($sunAfterEpiphany < $lent1) $addRow($sunAfterEpiphany, 'baptism_of_christ', 'The Baptism of Christ', 'sunday');

    $thirdBeforeLent  = $lent1->modify('-21 days');
    $secondBeforeLent = $lent1->modify('-14 days');
    $nextBeforeLent   = $lent1->modify('-7 days');

    $cursor = $sunAfterEpiphany->modify('+7 days');
    $n = 2;
    while ($cursor < $thirdBeforeLent) {
        $addRow($cursor, "epiphany_$n", ordinal($n) . " Sunday of Epiphany", 'sunday');
        $cursor = $cursor->modify('+7 days');
        $n++;
    }

    $addRow($thirdBeforeLent,  'third_before_lent',  'Third Sunday before Lent', 'sunday');
    $addRow($secondBeforeLent, 'second_before_lent', 'Second Sunday before Lent', 'sunday');
    $addRow($nextBeforeLent,   'next_before_lent',   'Sunday next before Lent (Transfiguration)', 'sunday');

    // Lent 1–5 with Mothering/Passion
    $cursor = $lent1;
    for ($i=1; $i<=5; $i++) {
        if ($i === 4) $addRow($cursor, 'mothering_sunday', 'The Fourth Sunday of Lent (Mothering Sunday)', 'sunday');
        elseif ($i === 5) $addRow($cursor, 'passion_sunday', 'The Fifth Sunday of Lent (Passion Sunday)', 'sunday');
        else $addRow($cursor, "lent_$i", ordinal($i) . " Sunday of Lent", 'sunday');
        $cursor = $cursor->modify('+7 days');
    }

    $addRow($palmSunday, 'palm_sunday', 'Palm Sunday', 'sunday');

    // Easter Sundays (NOTE: Easter Day itself is handled as a principal feast below, not as a separate Sunday row)
    // If you'd rather have Easter Day as a Sunday row, uncomment next line and remove the PF row for easter_day.
    // $addRow($easter, 'easter_day', 'Easter Day', 'sunday');

    for ($i=2; $i<=6; $i++) {
        $d = $easter->modify('+' . (7*($i-1)) . ' days');
        $addRow($d, "easter_$i", ordinal($i) . " Sunday of Easter", 'sunday');
    }
    $addRow($ascensionDay->modify('next sunday'), 'sunday_after_ascension', 'Sunday after Ascension Day', 'sunday');

    $addRow($pentecost, 'pentecost_sunday', 'Pentecost', 'sunday');
    $addRow($trinitySunday, 'trinity_sunday_sunday', 'Trinity Sunday', 'sunday');

    // Sundays after Trinity up to Christ the King
    $cursor = $trinitySunday->modify('+7 days');
    $count = 1;
    while ($cursor <= $christTheKing) {
        if ($cursor == $christTheKing) {
            $addRow($cursor, 'christ_the_king_sunday', 'Christ the King', 'sunday');
            break;
        }
        $addRow($cursor, "after_trinity_$count", ordinal($count) . " Sunday after Trinity", 'sunday');
        $cursor = $cursor->modify('+7 days');
        $count++;
    }

    // -------------------------
    // 2) Principal Feasts
    // -------------------------
    $addRow($easter,       'easter_day',    'Easter Day',    'principal_feast');
    $addRow($ascensionDay, 'ascension_day', 'Ascension Day', 'principal_feast');
    $addRow($pentecost,    'pentecost',     'Pentecost',     'principal_feast');
    $addRow($trinitySunday,'trinity_sunday','Trinity Sunday','principal_feast');

    $principalFeastsFixed = [
        ['12-25','christmas_day','Christmas Day'],
        ['01-06','epiphany','Epiphany'],
        ['02-02','candlemas','The Presentation of Christ (Candlemas)'],
        ['03-25','annunciation','The Annunciation'],
        ['11-01','all_saints','All Saints’ Day'],
    ];

    foreach ([$litYear - 1, $litYear] as $y) {
        foreach ($principalFeastsFixed as [$md, $key, $name]) {
            $d = new DateTimeImmutable(sprintf('%04d-%s 12:00:00', $y, $md), $tz);

            // Transfer Annunciation if in PalmSun..2ndSunEaster => Mon after 2nd Sun of Easter
            if ($key === 'annunciation' && $d >= $transferWindowStart && $d <= $transferWindowEnd) {
                $from = $d;
                $d = $monAfterSecondSundayEaster;
                $addRow($d, $key, $name, 'principal_feast', $from);
            } else {
                $addRow($d, $key, $name, 'principal_feast');
            }
        }
    }

    // -------------------------
    // 3) Principal Holy Days
    // -------------------------
    $addRow($ashWednesday,   'ash_wednesday',   'Ash Wednesday', 'principal_holy_day');
    $addRow($holyWeekMon,    'holy_week_mon',   'Monday of Holy Week', 'principal_holy_day');
    $addRow($holyWeekTue,    'holy_week_tue',   'Tuesday of Holy Week', 'principal_holy_day');
    $addRow($holyWeekWed,    'holy_week_wed',   'Wednesday of Holy Week', 'principal_holy_day');
    $addRow($maundyThursday, 'maundy_thursday', 'Maundy Thursday', 'principal_holy_day');
    $addRow($goodFriday,     'good_friday',     'Good Friday', 'principal_holy_day');
    $addRow($holySaturday,   'holy_saturday',   'Holy Saturday', 'principal_holy_day');

    // -------------------------
    // 4) Festivals (tight) + transfer rules
    // -------------------------
    $festivalsFixed = [
        ['01-01','naming','The Naming and Circumcision of Jesus'],
        ['03-19','st_joseph','Joseph of Nazareth'],
        ['04-23','st_george','St George'],
        ['04-25','st_mark','St Mark'],
        ['11-30','st_andrew','St Andrew'],
        ['12-26','st_stephen','St Stephen'],
        ['12-27','st_john','St John'],
        ['12-28','holy_innocents','The Holy Innocents'],
        ['05-01','philip_and_james','SS Philip and James'],
        ['06-11','st_barnabas','St Barnabas'],
        ['06-29','peter_and_paul','SS Peter and Paul'],
        ['07-22','mary_magdalene','St Mary Magdalene'],
        ['08-06','transfiguration','The Transfiguration'],
        ['08-15','bvm','The Blessed Virgin Mary'],
        ['09-14','holy_cross','Holy Cross Day'],
        ['09-29','michael_and_all_angels','Michael and All Angels'],
        ['10-18','st_luke','St Luke'],
        ['10-28','simon_and_jude','SS Simon and Jude'],
    ];

    // Determine whether George & Mark are in the transfer window in THIS liturgical year span
    $georgeInWindow = false;
    $markInWindow = false;
    foreach ([$litYear - 1, $litYear] as $y) {
        $g = new DateTimeImmutable(sprintf('%04d-04-23 12:00:00', $y), $tz);
        $m = new DateTimeImmutable(sprintf('%04d-04-25 12:00:00', $y), $tz);
        if ($g >= $transferWindowStart && $g <= $transferWindowEnd) $georgeInWindow = true;
        if ($m >= $transferWindowStart && $m <= $transferWindowEnd) $markInWindow = true;
    }

    foreach ([$litYear - 1, $litYear] as $y) {
        foreach ($festivalsFixed as [$md, $key, $name]) {
            $d = new DateTimeImmutable(sprintf('%04d-%s 12:00:00', $y, $md), $tz);
            $from = null;

            // Naming: if Sunday, transfer to Monday 2 Jan
            if ($key === 'naming' && (int)$d->format('N') === 7) {
                $from = $d;
                $d = $d->modify('+1 day');
                $addRow($d, $key, $name, 'festival', $from);
                continue;
            }

            // Joseph/George/Mark: if in PalmSun..2ndSunEaster window, move to Mon after 2nd Sun of Easter
            if (in_array($key, ['st_joseph','st_george','st_mark'], true)
                && $d >= $transferWindowStart && $d <= $transferWindowEnd
            ) {
                $from = $d;
                if ($key === 'st_mark' && $georgeInWindow && $markInWindow) $d = $tueAfterSecondSundayEaster;
                else $d = $monAfterSecondSundayEaster;

                $addRow($d, $key, $name, 'festival', $from);
                continue;
            }

            // Otherwise: festival on Sunday => Monday
            $td = $transfer_if_sunday($d);
            if ($td != $d) {
                $from = $d;
                $d = $td;
                $addRow($d, $key, $name, 'festival', $from);
            } else {
                $addRow($d, $key, $name, 'festival');
            }
        }
    }

    // Sort rows (date, then type precedence, then key)
    $typeOrder = ['principal_feast'=>1,'principal_holy_day'=>2,'sunday'=>3,'festival'=>4];
    usort($rows, function($a, $b) use ($typeOrder) {
        $c = strcmp($a['date'], $b['date']);
        if ($c !== 0) return $c;
        $c = ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99);
        if ($c !== 0) return $c;
        return strcmp($a['key'], $b['key']);
    });

    return $rows;
}

// Example:
$rows = liturgical_year_observances_rows(2027);
foreach ($rows as $r) {
    $xfer = $r['transferred_from'] ? " (transferred from {$r['transferred_from']})" : "";
    echo "{$r['date']}  {$r['type']}  {$r['name']}{$xfer}\n";
}
