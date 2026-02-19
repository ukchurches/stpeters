<?php
<?php
$file = $_POST['file'] ?? '';
$text = $_POST['text'] ?? '';

$map = [
    'projectedtext' => 'projectedtext.txt',
    'posturetext'   => 'posturetext.txt',
    'footertext'    => 'footertext.txt',
    'music'         => 'music.txt',
];

if (!isset($map[$file])) {
    http_response_code(400);
    exit('Invalid target');
}

file_put_contents(__DIR__ . '/' . $map[$file], $text);
$back = $_SERVER['HTTP_REFERER'] ?? '/';
echo '<a href="' . htmlspecialchars($back) . '">Back</a>';

?>