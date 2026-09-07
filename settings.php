<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_admin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_check($_POST['csrf_token'] ?? '')) {
        set_setting('daily_usage_limit_kwh', (string) (float)$_POST['daily_usage_limit_kwh']);
        set_setting('submission_deadline_time', $_POST['submission_deadline_time']);
        set_setting('whatsapp_admin_number', trim($_POST['whatsapp_admin_number']));
        set_setting('whatsapp_api_url', trim($_POST['whatsapp_api_url']));
        set_setting('whatsapp_api_token', trim($_POST['whatsapp_api_token']));
        set_setting('app_version', $_POST['app_version'] === '2' ? '2' : '1');
        $msg = 'Settings saved.';
    }
}

$limit = get_setting('daily_usage_limit_kwh', 80);
$deadline = get_setting('submission_deadline_time', '10:00');
$wa_number = get_setting('whatsapp_admin_number', '');
$wa_url = get_setting('whatsapp_api_url', '');
$wa_token = get_setting('whatsapp_api_token', '');
$version = get_setting('app_version', '1');

$page_title = 'Settings';
include __DIR__ . '/includes/header.php';
?>
<h1>Settings</h1>
<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="card">
        <h3>Usage Alert Rules</h3>
        <div class="form-group">
            <label>Daily Usage Limit (kWh)</label>
            <input type="number" step="0.01" name="daily_usage_limit_kwh" value="<?= h($limit) ?>" required>
        </div>
        <div class="form-group">
            <label>Submission Deadline Time (Malaysia time)</label>
            <input type="time" name="submission_deadline_time" value="<?= h($deadline) ?>" required>
            <p class="hint">If a meter has no reading submitted by this time each day, a WhatsApp reminder is sent (via the cron job).</p>
        </div>
    </div>

    <div class="card">
        <h3>WhatsApp Gateway</h3>
        <div class="form-group">
            <label>Admin WhatsApp Number</label>
            <input type="text" name="whatsapp_admin_number" value="<?= h($wa_number) ?>" placeholder="60123456789">
        </div>
        <div class="form-group">
            <label>API URL</label>
            <input type="text" name="whatsapp_api_url" value="<?= h($wa_url) ?>">
        </div>
        <div class="form-group">
            <label>API Token</label>
            <input type="text" name="whatsapp_api_token" value="<?= h($wa_token) ?>">
        </div>
        <p class="hint">Default is set up for Fonnte (fonnte.com). See README.md to swap for Twilio/WABLAS/official WhatsApp Cloud API.</p>
    </div>

    <div class="card">
        <h3>App Version</h3>
        <div class="form-group">
            <label>Photo Capture Mode</label>
            <select name="app_version">
                <option value="1" <?= $version==='1'?'selected':'' ?>>Version 1 - Allow upload from gallery or camera</option>
                <option value="2" <?= $version==='2'?'selected':'' ?>>Version 2 - Force live camera capture only</option>
            </select>
            <p class="hint">Controls which submission flow the "Submit Reading" menu uses for Users.</p>
        </div>
    </div>

    <button type="submit">Save Settings</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
