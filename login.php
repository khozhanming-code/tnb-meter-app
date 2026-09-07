<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user_id']    = $u['id'];
            $_SESSION['user_name']  = $u['name'];
            $_SESSION['user_email'] = $u['email'];
            $_SESSION['user_role']  = $u['role'];
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="login-wrap">
    <div class="card">
        <h2>Login</h2>
        <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p class="hint">Demo: admin@example.com / admin123 (Admin), user@example.com / user123 (User) — after running setup_create_accounts.php once.</p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
