<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$saveDir = '/home/stpeters/public_html/created_templates';

// Get filename from query string
$file = (string)($_GET['file'] ?? '');
$file = trim($file);
$file = basename($file); // prevent ../ traversal

$exists = false;

// Only allow safe .html filenames
if ($file !== '' && preg_match('/^[A-Za-z0-9_-]+\.html$/i', $file)) {
    $exists = is_file($saveDir . '/' . $file);
}

echo json_encode([
    'exists' => $exists
]);
