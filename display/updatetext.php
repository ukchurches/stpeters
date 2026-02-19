<?php
$file = $_POST['file'] ?? '';
$text = $_POST['text'] ?? '';

$map = [
    'projectedtext' => 'projectedtext.txt',
    'posturetext'   =>'projectedposture.txt',
    'footertext'    => 'projectedfooter.txt',
    'music'         => 'projectedmusic.txt'
];

$gg='projectedtext.txt';



file_put_contents(__DIR__ . '/' . $gg, $text);
// do form processing here...

$back = $_SERVER['HTTP_REFERER'] ?? '/thank-you.html';
header("Location: $back");
exit;


?>
