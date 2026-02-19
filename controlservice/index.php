<?php
declare(strict_types=1);

/* === DATABASE CONFIG === */
$host = 'localhost';
$db   = 'stpeters';
$user = 'stpeters';
$pass = 'gKGTrPH77vGg4xdmI5rZJpre6';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->prepare(
        'SELECT saved_service_content 
         FROM saved_services 
         WHERE saved_service_id = :id'
    );
    $stmt->execute(['id' => 28]);

    $serviceContent = $stmt->fetchColumn() ?: '<p>No service content found.</p>';

} catch (Throwable $e) {
    $serviceContent = '<p>Error loading service.</p>';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Tabbed Content</title>

<style>
* { box-sizing: border-box; }

body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    margin: 40px;
}

.tabset > input[type="radio"] {
    position: absolute;
    left: -200vw;
}

.tabset > label {
    display: inline-block;
    padding: 10px 18px;
    margin-right: -1px;
    border: 1px solid #ccc;
    border-bottom: none;
    cursor: pointer;
    background: #f5f5f5;
}

.tabset > label:hover {
    background: #eee;
}

.tabset > input:checked + label {
    background: #fff;
    border-bottom: 1px solid #fff;
    z-index: 2;
}

.tab-panels {
    border: 1px solid #ccc;
    padding: 20px;
    background: #fff;
}

.tab-panel {
    display: none;
}

#tab1:checked ~ .tab-panels #Service,
#tab2:checked ~ .tab-panels #Sermon,
#tab3:checked ~ .tab-panels #Notices,
#tab4:checked ~ .tab-panels #Songs,
#tab5:checked ~ .tab-panels #Bible {
    display: block;
}
</style>
</head>

<body>

<div class="tabset">
    <input type="radio" name="tabset" id="tab1" checked>
    <label for="tab1">Service</label>

    <input type="radio" name="tabset" id="tab2">
    <label for="tab2">Sermon</label>

    <input type="radio" name="tabset" id="tab3">
    <label for="tab3">Notices</label>

    <input type="radio" name="tabset" id="tab4">
    <label for="tab4">Songs</label>

    <input type="radio" name="tabset" id="tab5">
    <label for="tab5">Bible</label>

    <div class="tab-panels">
        <section id="Service" class="tab-panel">
            <?= $serviceContent ?>
        </section>

        <section id="Sermon" class="tab-panel">
            sermon
        </section>

        <section id="Notices" class="tab-panel">
            notices
        </section>

        <section id="Songs" class="tab-panel">
            songs
        </section>

        <section id="Bible" class="tab-panel">
            bible
        </section>
    </div>
</div>

</body>
</html>
