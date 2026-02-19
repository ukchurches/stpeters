<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$saveDir  ='/home/stpeters/public_html/created_templates';
$maxBytes = 500 * 1024;

// Ensure directory exists
if (!is_dir($saveDir) && !mkdir($saveDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot create save directory']);
    exit;
}

// Get filename from query string
$template = (string)($_GET['template'] ?? '');
$template = trim($template);
$template = basename($template); // prevents ../ traversal

// Only allow safe filenames like communion_service.html
if ($template === '' || !preg_match('/^[A-Za-z0-9_-]+\.html$/', $template)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid or missing ?template=... (must be like name.html)'
    ]);
    exit;
}

// Read raw HTML fragment from request body
$html = file_get_contents('php://input');
if ($html === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No request body received']);
    exit;
}

if (strlen($html) > $maxBytes) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Content too large']);
    exit;
}

// Save EXACTLY the fragment (no wrapper HTML)
$path  = $saveDir . '/' . $template;
$bytes = file_put_contents($path, $html, LOCK_EX);

if ($bytes === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to write file']);
    exit;
}

echo json_encode([
    'ok' => true,
    'filename' => $template,
    'bytes' => $bytes,
    'saved_at' => gmdate('Y-m-d H:i:s') . ' UTC'
]);
