<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Disable buffering
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(true);

// Map SSE event names -> files to watch
$watch = [
  'projectedtext' => __DIR__ . '/projectedtext.txt',
  'posturetext'   => __DIR__ . '/projectedposture.txt',
  'footertext'    => __DIR__ . '/projectedfooter.txt',
  'music'         => __DIR__ . '/projectedmusic.txt',
];

$state = [];
foreach ($watch as $event => $path) {
  $state[$event] = ['mtime' => 0, 'hash' => ''];
}

echo "retry: 3000\n\n";
flush();

$id = 0;

while (true) {
  $sentAnything = false;

  foreach ($watch as $event => $path) {
    if (!file_exists($path)) continue;

    clearstatcache(false, $path);
    $mtime = filemtime($path);

    // Only reread if file timestamp changed
    if ($mtime !== $state[$event]['mtime']) {
      $text = trim((string)file_get_contents($path));
      $hash = md5($text);

      // Only emit if content actually changed
      if ($hash !== $state[$event]['hash']) {
        $id++;

        echo "event: $event\n";
        echo "id: $id\n";
        echo "data: " . json_encode([
          'text' => $text,
          'updated' => date('c'),
        ]) . "\n\n";

        flush();

        $state[$event]['hash']  = $hash;
        $state[$event]['mtime'] = $mtime;
        $sentAnything = true;
      } else {
        // mtime changed but content didn't (rare), still update mtime
        $state[$event]['mtime'] = $mtime;
      }
    }
  }

  if (connection_aborted()) break;

  // Keep-alive comment every ~15s so proxies don’t close the connection
  if (!$sentAnything) {
    static $lastPing = 0;
    if (time() - $lastPing >= 15) {
      echo ": ping\n\n";
      flush();
      $lastPing = time();
    }
  }

  usleep(250000); // 0.25s polling
}
