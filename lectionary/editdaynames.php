<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['dayname'])) {
    $stmt = $pdo->prepare(
        "UPDATE lectionary_sundaysandholydays SET dayname = ? WHERE sunday_holyday_id = ?"
    );
    $stmt->execute([trim($_POST['dayname']), (int)$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
}

// Fetch all rows
$rows = $pdo->query(
    "SELECT sunday_holyday_id, orderby, season, type, dayname
     FROM lectionary_sundaysandholydays
     ORDER BY orderby ASC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Day Names</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    margin: 30px;
    font-size: 14px;
}
h1 { margin-bottom: 16px; font-size: 1.4rem; }
table {
    border-collapse: collapse;
    width: 100%;
}
th, td {
    border: 1px solid #ccc;
    padding: 6px 10px;
    text-align: left;
    vertical-align: middle;
}
th { background: #f0f0f0; }
tr:nth-child(even) td { background: #fafafa; }
tr:hover td { background: #f5f9ff; }
.dayname-text { display: inline; }
.dayname-input {
    display: none;
    width: 100%;
    padding: 3px 6px;
    font-size: 13px;
    border: 1px solid #888;
    border-radius: 3px;
}
button {
    padding: 3px 10px;
    font-size: 12px;
    cursor: pointer;
    border-radius: 3px;
    border: 1px solid #aaa;
    background: #fff;
    margin-left: 4px;
}
button.edit-btn  { color: #0055cc; }
button.save-btn  { color: #007a00; display: none; }
button.cancel-btn{ color: #aa0000; display: none; }
.saved-flash {
    color: green;
    font-size: 12px;
    margin-left: 6px;
    display: none;
}
</style>
</head>
<body>

<h1>Edit Day Names &mdash; lectionary_sundaysandholydays</h1>

<table>
    <thead>
        <tr>
            <th>Order</th>
            <th>Season</th>
            <th>Type</th>
            <th>Day Name</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
    <tr data-id="<?= (int)$row['sunday_holyday_id'] ?>">
        <td><?= htmlspecialchars($row['orderby']) ?></td>
        <td><?= htmlspecialchars($row['season']) ?></td>
        <td><?= htmlspecialchars($row['type']) ?></td>
        <td>
            <span class="dayname-text"><?= htmlspecialchars($row['dayname']) ?></span>
            <input class="dayname-input" type="text" value="<?= htmlspecialchars($row['dayname']) ?>">
        </td>
        <td style="white-space:nowrap">
            <button class="edit-btn">Edit</button>
            <button class="save-btn">Save</button>
            <button class="cancel-btn">Cancel</button>
            <span class="saved-flash">Saved</span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script src="/assets/jquery-3.2.1.min.js"></script>
<script>
$(function () {

    // Edit button: show input, hide text
    $(document).on('click', '.edit-btn', function () {
        var $tr = $(this).closest('tr');
        $tr.find('.dayname-text').hide();
        $tr.find('.dayname-input').show().focus().select();
        $(this).hide();
        $tr.find('.save-btn, .cancel-btn').show();
    });

    // Cancel button: restore original value
    $(document).on('click', '.cancel-btn', function () {
        var $tr = $(this).closest('tr');
        var original = $tr.find('.dayname-text').text();
        $tr.find('.dayname-input').val(original).hide();
        $tr.find('.dayname-text').show();
        $tr.find('.edit-btn').show();
        $(this).hide();
        $tr.find('.save-btn').hide();
    });

    // Save button: POST via AJAX
    $(document).on('click', '.save-btn', function () {
        var $tr   = $(this).closest('tr');
        var id    = $tr.data('id');
        var name  = $tr.find('.dayname-input').val().trim();
        var $btn  = $(this);

        $.post('', { id: id, dayname: name }, function (resp) {
            if (resp.ok) {
                $tr.find('.dayname-text').text(name).show();
                $tr.find('.dayname-input').hide();
                $btn.hide();
                $tr.find('.cancel-btn').hide();
                $tr.find('.edit-btn').show();
                $tr.find('.saved-flash').show().delay(1500).fadeOut();
            }
        }, 'json').fail(function () {
            alert('Save failed. Please try again.');
        });
    });

    // Allow Enter to save, Escape to cancel
    $(document).on('keydown', '.dayname-input', function (e) {
        if (e.key === 'Enter')  $(this).closest('tr').find('.save-btn').click();
        if (e.key === 'Escape') $(this).closest('tr').find('.cancel-btn').click();
    });

});
</script>
</body>
</html>
