<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

global $pdo;
$user = current_user();

$meter_filter = isset($_GET['meter_id']) ? (int)$_GET['meter_id'] : 0;

$sql = "SELECT r.*, m.name AS meter_name, u.name AS user_name
        FROM meter_readings r
        JOIN meters m ON m.id = r.meter_id
        JOIN users u ON u.id = r.user_id
        WHERE 1=1";
$params = [];

if ($user['role'] !== 'admin') {
    $sql .= " AND r.user_id = ?";
    $params[] = $user['id'];
}
if ($meter_filter) {
    $sql .= " AND r.meter_id = ?";
    $params[] = $meter_filter;
}
$sql .= " ORDER BY r.reading_date DESC, r.reading_time DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$readings = $stmt->fetchAll();

$meters = get_meters_for_user($user['id'], $user['role']);

$page_title = 'Readings';
include __DIR__ . '/includes/header.php';
?>
<h1><?= $user['role'] === 'admin' ? 'All Readings' : 'My Readings' ?></h1>

<div class="card">
    <form method="get" style="display:flex; gap:10px; align-items:flex-end;">
        <div class="form-group" style="flex:1; margin-bottom:0;">
            <label>Filter by Meter</label>
            <select name="meter_id" onchange="this.form.submit()">
                <option value="0">All Meters</option>
                <?php foreach ($meters as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $meter_filter == $m['id'] ? 'selected' : '' ?>><?= h($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <tr>
            <th>Photo</th>
            <th>Meter</th>
            <th>Reading (kWh)</th>
            <th>Date</th>
            <th>Time</th>
            <th>Submitted By</th>
            <th>Method</th>
        </tr>
        <?php foreach ($readings as $r): ?>
        <tr>
            <td><a href="<?= UPLOAD_URL . h($r['photo_path']) ?>" target="_blank">
                <img class="thumb" src="<?= UPLOAD_URL . h($r['photo_path']) ?>" alt="meter photo"></a></td>
            <td><?= h($r['meter_name']) ?></td>
            <td><?= h($r['reading_value']) ?></td>
            <td><?= h(date('d/M/Y', strtotime($r['reading_date']))) ?></td>
            <td><?= h(date('h:i A', strtotime($r['reading_time']))) ?></td>
            <td><?= h($r['user_name']) ?></td>
            <td><?= $r['capture_method'] === 'v2_live_camera' ? 'Live Camera' : 'Upload' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($readings)): ?>
        <tr><td colspan="7">No readings yet.</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
