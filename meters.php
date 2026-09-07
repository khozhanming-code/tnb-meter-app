<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_admin();

global $pdo;
$msg = '';

// Add meter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (csrf_check($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare("INSERT INTO meters (name, location, tnb_account_no) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['name']), trim($_POST['location']), trim($_POST['tnb_account_no'])]);
        $msg = 'Meter added.';
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE meters SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . BASE_URL . '/meters.php');
    exit;
}

$meters = $pdo->query("SELECT * FROM meters ORDER BY id DESC")->fetchAll();

$page_title = 'Meters';
include __DIR__ . '/includes/header.php';
?>
<h1>Meters</h1>
<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<div class="card">
    <h3>Add New Meter</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>Meter / Building Name</label>
            <input type="text" name="name" required placeholder="e.g. Building A - Main Meter">
        </div>
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" placeholder="e.g. Ground Floor, Building A">
        </div>
        <div class="form-group">
            <label>TNB Account No. (optional)</label>
            <input type="text" name="tnb_account_no">
        </div>
        <button type="submit">Add Meter</button>
    </form>
</div>

<div class="card">
    <h3>All Meters</h3>
    <table>
        <tr><th>Name</th><th>Location</th><th>TNB Acc No</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($meters as $m): ?>
        <tr>
            <td><?= h($m['name']) ?></td>
            <td><?= h($m['location']) ?></td>
            <td><?= h($m['tnb_account_no']) ?></td>
            <td><span class="badge <?= $m['status']==='active'?'badge-ok':'badge-over' ?>"><?= h($m['status']) ?></span></td>
            <td><a href="?toggle=<?= $m['id'] ?>" class="btn btn-secondary" onclick="return confirm('Toggle status?')">Toggle</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
