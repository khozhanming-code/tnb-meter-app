<?php
// ==========================================================
// RUN THIS ONCE in your browser right after importing database.sql:
//   http://localhost/tnb-meter-app/setup_create_accounts.php
// It sets working passwords for the two seed accounts.
// DELETE THIS FILE after running it once.
// ==========================================================
require_once __DIR__ . '/config/db.php';

$accounts = [
    'admin@example.com' => 'admin123',
    'user@example.com'  => 'user123',
];

$results = [];
foreach ($accounts as $email => $plain) {
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
    $results[] = "$email updated (" . $stmt->rowCount() . " row).";
}
?>
<!DOCTYPE html>
<html><head><title>Setup Accounts</title></head>
<body style="font-family: sans-serif; max-width:600px; margin:60px auto;">
<h2>Account setup complete</h2>
<ul>
<?php foreach ($results as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?>
</ul>
<p><strong>Login credentials:</strong></p>
<ul>
<li>Admin: admin@example.com / admin123</li>
<li>User: user@example.com / user123</li>
</ul>
<p style="color:#c0392b;"><strong>Important:</strong> Delete this file (setup_create_accounts.php) now, and change these passwords after first login.</p>
<p><a href="login.php">Go to Login</a></p>
</body></html>
