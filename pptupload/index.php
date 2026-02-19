<?php
declare(strict_types=1);

/*
 * PPT Upload → slide images
 * FIXED: background conversion now uses PHP CLI, not php-cgi
 */

const BASE_DIR   = '/home/stpeters/public_html/pptupload';
const UPLOAD_DIR = BASE_DIR . '/uploads';
const OUT_DIR    = BASE_DIR . '/generated_slides';
const JOB_DIR    = BASE_DIR . '/jobs';

const MAX_BYTES  = 250 * 1024 * 1024;

@date_default_timezone_set('Europe/London');

/* ---------------- CLI MODE ---------------- */
if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') === '--convert') {
        run_conversion((string)$argv[2]);
    }
    exit;
}

/* ---------------- ROUTING ---------------- */
$action = $_GET['action'] ?? '';

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    ensure_dirs();

    try {
        if (!isset($_FILES['ppt'])) {
            throw new RuntimeException('No file uploaded');
        }

        $job = sha1(uniqid('', true));
        $file = $_FILES['ppt'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error');
        }

        if ($file['size'] > MAX_BYTES) {
            throw new RuntimeException('File too large');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['ppt', 'pptx'], true)) {
            throw new RuntimeException('Only PPT/PPTX allowed');
        }

        $uploadPath = UPLOAD_DIR . "/{$job}.{$ext}";
        move_uploaded_file($file['tmp_name'], $uploadPath);

        write_status($job, [
            'ok' => true,
            'job' => $job,
            'stage' => 'queued',
            'message' => 'Queued for conversion…',
            'createdAt' => date('c'),
        ]);

        /* ---- IMPORTANT FIX: use PHP CLI, not php-cgi ---- */
        $phpCli = trim((string)shell_exec('command -v php 2>/dev/null'));
        if ($phpCli === '') {
            $phpCli = '/usr/bin/php';
        }

        $cmd =
            escapeshellcmd($phpCli) . ' ' .
            escapeshellarg(__FILE__) .
            ' --convert ' . escapeshellarg($job) .
            ' > ' . escapeshellarg(JOB_DIR . "/{$job}.spawn.log") . ' 2>&1 &';

        exec($cmd);

        echo json_encode(['ok' => true, 'job' => $job]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'status') {
    header('Content-Type: application/json');
    $job = $_GET['job'] ?? '';
    $status = read_status($job);

    if (!$status) {
        http_response_code(404);
        echo json_encode(['ok' => false]);
        exit;
    }

    echo json_encode($status);
    exit;
}

/* ---------------- PAGE ---------------- */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>PPT Upload → Slides</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: system-ui, sans-serif; background:#f6f7f9; padding:30px }
.card { background:#fff; max-width:900px; margin:auto; padding:20px;
        border-radius:14px; box-shadow:0 10px 25px rgba(0,0,0,.08) }
button { padding:10px 16px; border:0; border-radius:12px;
         background:#1a73e8; color:#fff; font-weight:600 }
.progress { margin-top:15px; height:12px; background:#eee; border-radius:999px }
.progress > div { height:100%; width:0; background:#1a73e8 }
.overlay {
  position:fixed; inset:0; background:rgba(255,255,255,.85);
  display:none; align-items:center; justify-content:center; z-index:9999;
}
.spinner {
  width:48px; height:48px; border-radius:50%;
  border:6px solid #ddd; border-top-color:#1a73e8;
  animation:spin 1s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg) } }
.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; margin-top:20px }
.grid img { width:100%; border-radius:10px }
</style>
</head>
<body>

<div class="card">
<h1>Upload PowerPoint</h1>

<form id="form">
<input type="file" id="ppt" accept=".ppt,.pptx" required>
<button>Upload & Convert</button>
<div class="progress"><div id="bar"></div></div>
</form>

<div class="grid" id="grid"></div>
</div>

<div class="overlay" id="overlay">
  <div>
    <div class="spinner"></div>
    <p>Converting slides…</p>
  </div>
</div>

<script>
const form = document.getElementById('form');
const bar = document.getElementById('bar');
const overlay = document.getElementById('overlay');
const grid = document.getElementById('grid');

form.onsubmit = e => {
  e.preventDefault();
  bar.style.width = '0%';
  grid.innerHTML = '';

  const fd = new FormData();
  fd.append('ppt', document.getElementById('ppt').files[0]);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '?action=upload');

  xhr.upload.onprogress = e => {
    if (e.lengthComputable) {
      bar.style.width = (e.loaded / e.total * 100) + '%';
    }
  };

  xhr.onload = () => {
    const r = JSON.parse(xhr.responseText);
    overlay.style.display = 'flex';
    poll(r.job);
  };

  xhr.send(fd);
};

function poll(job) {
  const t = setInterval(async () => {
    const r = await fetch(`?action=status&job=${job}`);
    const j = await r.json();

    if (j.stage === 'done') {
      clearInterval(t);
      overlay.style.display = 'none';
      j.slides.forEach(u => {
        const img = document.createElement('img');
        img.src = u;
        grid.appendChild(img);
      });
    }
  }, 1000);
}
</script>
</body>
</html>
<?php

/* ---------------- HELPERS ---------------- */

function ensure_dirs(): void {
    foreach ([UPLOAD_DIR, OUT_DIR, JOB_DIR] as $d) {
        if (!is_dir($d)) mkdir($d, 0775, true);
    }
}

function status_path(string $job): string {
    return JOB_DIR . "/{$job}.json";
}

function write_status(string $job, array $data): void {
    file_put_contents(status_path($job), json_encode($data));
}

function read_status(string $job): ?array {
    $p = status_path($job);
    return is_file($p) ? json_decode(file_get_contents($p), true) : null;
}

function run_conversion(string $job): void {
    putenv('HOME=' . JOB_DIR); // LibreOffice needs this

    $ppt = glob(UPLOAD_DIR . "/{$job}.*")[0] ?? null;
    if (!$ppt) return;

    write_status($job, ['ok'=>true,'job'=>$job,'stage'=>'converting']);

    $out = OUT_DIR . "/{$job}";
    mkdir($out, 0775, true);

    exec("soffice --headless --convert-to pdf --outdir " .
         escapeshellarg($out) . ' ' . escapeshellarg($ppt));

    exec("pdftoppm -png {$out}/*.pdf {$out}/slide");

    $slides = array_map(
        fn($f) => "/pptupload/generated_slides/{$job}/" . basename($f),
        glob("{$out}/slide-*.png")
    );

    write_status($job, [
        'ok'=>true,
        'job'=>$job,
        'stage'=>'done',
        'slides'=>$slides
    ]);
}
