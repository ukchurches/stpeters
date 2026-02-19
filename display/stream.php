<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(true);

$watch = [
    'projectedtext' => __DIR__ . '/projectedtext.txt',
    'projectedposture'   => __DIR__ . '/projectedposture.txt',
    'projectedfooter'    => __DIR__ . '/projectedfooter.txt',
    'projectedmusic'         => __DIR__ . '/projectedmusic.txt',
];

$state = [];
foreach ($watch as $event => $path) {
    $state[$event] = ['mtime' => 0, 'hash' => ''];
}

$id = 0;

while (true) {

    foreach ($watch as $event => $path) {
        if (!file_exists($path)) continue;

        clearstatcache(false, $path);
        $mtime = filemtime($path);

        if ($mtime !== $state[$event]['mtime']) {
            $text = trim((string) file_get_contents($path));
            $hash = md5($text);

            if ($hash !== $state[$event]['hash']) {
                $id++;

                echo "event: $event\n";
                echo "id: $id\n";
                echo "data: " . json_encode(['text' => $text]) . "\n\n";
                flush();

                $state[$event]['hash']  = $hash;
                $state[$event]['mtime'] = $mtime;
            } else {
                $state[$event]['mtime'] = $mtime;
            }
        }
    }

    if (connection_aborted()) break;

    usleep(250000);
}
