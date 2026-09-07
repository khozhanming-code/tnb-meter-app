<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_admin();

global $pdo;
$msg = '';
$error = '';

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    if (csrf_check($_POST['csrf_token'] ?? '')) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'Email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role]);
            $new_id = $pdo->lastInsertId();

            if (!empty($_POST['meter_ids'])) {
                $ins = $pdo->prepare("INSERT INTO user_meters (user_id, meter_id) VALUES (?, ?)");
                foreach ($_POST['meter_ids'] as $mid) {
                    $ins->execute([$new_id, (int)$mid]);
                }
            }
            $msg = 'User created.';
        }
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . BASE_URL . '/users.php');
    exit;
}

$users = $pdo->query(
    "SELECT u.*, GROUP_CONCAT(m.name SEPARATOR ', ') AS meters
     FROM users u
     LEFT JOIN user_meters um ON um.user_id = u.id
     LEFT JOIN meters m ON m.id = um.meter_id
     GROUP BY u.id ORDER BY u.id DESC"
)->fetchAll();

$meters = get_all_meters(true);

$page_title = 'Users';
include __DIR__ . '/includes/header.php';
?>
<h1>Users</h1>
<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
    <h3>Add New User</h3>
    <form method="post">
        <input type="hidden" name="action" value="add_user">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="user">User (submits readings)</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label>Assign Meter(s) (for role = User)</label>
            <?php foreach ($meters as $m): ?>
                <label style="font-weight:normal;">
                    <input type="checkbox" name="meter_ids[]" value="<?= $m['id'] ?>"> <?= h($m['name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <button type="submit">Add User</button>
    </form>
</div>

<div class="card">
    <h3>All Users</h3>
    <table>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Assigned Meters</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= h($u['name']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['role']) ?></td>
            <td><?= h($u['meters'] ?: '-') ?></td>
            <td><span class="badge <?= $u['status']==='active'?'badge-ok':'badge-over' ?>"><?= h($u['status']) ?></span></td>
            <td><a href="?toggle=<?= $u['id'] ?>" class="btn btn-secondary" onclick="return confirm('Toggle status?')">Toggle</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
