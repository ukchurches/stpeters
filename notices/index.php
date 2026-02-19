<?php
declare(strict_types=1);

/**
 * ACNY ICS -> HTML fragments (no WordPress required)
 *
 * Usage:
 *   /acny_fragments.php?ics=https://www.achurchnearyou.com/church/18183/service-and-events/feed/
 * Optional:
 *   &format=html   (default)
 *   &format=json
 */

header('X-Content-Type-Options: nosniff');

$icsUrl = (string)($_GET['ics'] ?? 'https://www.achurchnearyou.com/church/18183/service-and-events/feed/');
$format = (string)($_GET['format'] ?? 'html');

$tz = new DateTimeZone('Europe/London');
$now = new DateTimeImmutable('now', $tz);

// Your original window: now -> end of next Sunday
$nextSunday = $now->modify('next sunday')->setTime(0, 0, 0);
$endSunday  = $nextSunday->modify('+7 days'); // start of the Sunday after next

// --- helpers ---------------------------------------------------------------

function http_get(string $url): string {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: ACNY-Fragments/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        throw new RuntimeException("Failed to fetch ICS: $url");
    }
    return $data;
}

function ics_unfold(string $ics): string {
    // Unfold folded lines: CRLF + space/tab continuation
    return preg_replace("/\r\n[ \t]|[\n\r][ \t]/", '', $ics) ?? $ics;
}

function ics_get(string $vevent, string $prop): ?string {
    // Match: PROP:VALUE or PROP;PARAM=...:VALUE
    if (preg_match('/^' . preg_quote($prop, '/') . '(?:;[^:]*)?:(.*)$/mi', $vevent, $m)) {
        return trim($m[1]);
    }
    return null;
}

function ics_text(string $s): string {
    // ICS text escaping: \n, \, \;, \,
    $s = str_replace(['\\n', '\\N'], "\n", $s);
    $s = str_replace(['\\,', '\\;', '\\\\'], [',', ';', '\\'], $s);
    return $s;
}

function parse_ics_dt(string $raw, DateTimeZone $tz): ?DateTimeImmutable {
    if ($raw === '') return null;

    // Common patterns you’ll see:
    //  - 20260215T103000Z
    //  - 20260215T103000
    //  - 20260215
    $raw = trim($raw);

    // All-day date
    if (preg_match('/^\d{8}$/', $raw)) {
        return DateTimeImmutable::createFromFormat('Ymd', $raw, $tz) ?: null;
    }

    // Date-time (maybe Z)
    if (preg_match('/^\d{8}T\d{6}Z$/', $raw)) {
        $dt = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $raw, new DateTimeZone('UTC'));
        return $dt ? $dt->setTimezone($tz) : null;
    }

    if (preg_match('/^\d{8}T\d{6}$/', $raw)) {
        return DateTimeImmutable::createFromFormat('Ymd\THis', $raw, $tz) ?: null;
    }

    // Fallback
    $ts = strtotime($raw);
    return $ts ? (new DateTimeImmutable('@' . $ts))->setTimezone($tz) : null;
}

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// --- main ------------------------------------------------------------------

try {
    $ics = ics_unfold(http_get($icsUrl));
} catch (Throwable $e) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit;
}

preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $ics, $matches);

$events = [];

foreach ($matches[1] as $vevent) {
    $dtStartRaw = ics_get($vevent, 'DTSTART') ?? '';
    $dtEndRaw   = ics_get($vevent, 'DTEND') ?? '';

    $start = parse_ics_dt($dtStartRaw, $tz);
    if (!$start) continue;

    // Filter window (same logic as your shortcode)
    if ($start < $now || $start >= $endSunday) continue;

    $end = parse_ics_dt($dtEndRaw, $tz);

    $summary = ics_text(ics_get($vevent, 'SUMMARY') ?? 'Event');
    $desc    = ics_text(ics_get($vevent, 'DESCRIPTION') ?? '');
    $loc     = ics_text(ics_get($vevent, 'LOCATION') ?? '');
    $url     = trim(ics_get($vevent, 'URL') ?? '');

    // If no URL field, try first URL in DESCRIPTION
    if ($url === '' && preg_match('/https?:\/\/[^\s"]+/i', $desc, $m)) {
        $url = $m[0];
    }

    $events[] = [
        'start' => $start,
        'end'   => $end,
        'title' => $summary,
        'desc'  => $desc,
        'loc'   => $loc,
        'url'   => $url,
    ];
}

// Sort by start time
usort($events, fn($a, $b) => $a['start'] <=> $b['start']);

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    $out = array_map(function ($e) {
        $when = $e['start']->format('D j M Y, g:ia');
        return [
            'title' => $e['title'],
            'when'  => $when,
            'url'   => $e['url'],
            'html'  => render_event_fragment($e),
        ];
    }, $events);

    echo json_encode(['ok' => true, 'count' => count($events), 'events' => $out], JSON_UNESCAPED_SLASHES);
    exit;
}

// default HTML
header('Content-Type: text/html; charset=utf-8');

echo "<div class=\"acny-events\">\n";
echo "<style>
.acny-events{font-family:system-ui,-apple-system,Segoe UI,sans-serif;max-width:760px;margin:16px auto;padding:0 12px}
.acny-event{border:1px solid #e5e5e5;border-radius:10px;padding:14px;margin:12px 0;background:#fff}
.acny-event h3{margin:0 0 6px;font-size:1.2rem}
.acny-meta{opacity:.8;font-size:.95rem;margin:0 0 8px}
.acny-desc{white-space:pre-wrap;margin:10px 0 0}
.acny-link{display:inline-block;margin-top:10px;text-decoration:none}
</style>\n";

foreach ($events as $e) {
    echo render_event_fragment($e);
}
echo "</div>\n";

// --- renderer --------------------------------------------------------------

function render_event_fragment(array $e): string {
    $start = $e['start'];
    $end   = $e['end'];

    $when = $start->format('D j M Y, g:ia');
    if ($end instanceof DateTimeImmutable) {
        // Only show end time if same day
        if ($end->format('Y-m-d') === $start->format('Y-m-d')) {
            $when .= '–' . $end->format('g:ia');
        }
    }

    $title = esc($e['title']);
    $loc   = trim($e['loc']) !== '' ? ' • ' . esc($e['loc']) : '';
    $desc  = trim($e['desc']) !== '' ? '<div class="acny-desc">' . esc($e['desc']) . '</div>' : '';
    $url   = trim($e['url']);

    $link = $url !== '' ? '<a class="acny-link" href="' . esc($url) . '" target="_blank" rel="noopener">More info</a>' : '';

    $iso = esc($start->format(DateTimeInterface::ATOM));

    return <<<HTML
<article class="acny-event" data-start="{$iso}">
  <h3>{$title}</h3>
  <div class="acny-meta">{$when}{$loc}</div>
  {$desc}
  {$link}
</article>

HTML;
}
