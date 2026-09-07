<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_admin();

$today = date('Y-m-d');
$limit = (float) get_setting('daily_usage_limit_kwh', 80);
$meters = get_all_meters(true);

$rows = [];
foreach ($meters as $m) {
    $today_reading = get_reading_for_date($m['id'], $today);
    $prev_reading  = get_latest_reading($m['id'], $today);
    $usage = null;
    if ($today_reading && $prev_reading) {
        $usage = (float)$today_reading['reading_value'] - (float)$prev_reading['reading_value'];
    }
    $rows[] = [
        'meter'   => $m,
        'today'   => $today_reading,
        'usage'   => $usage,
        'over'    => $usage !== null ? $usage - $limit : null,
    ];
}

$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<h1>Admin Dashboard</h1>
<p>Today: <strong><?= h(date('d/F/Y')) ?></strong> | Daily usage limit: <strong><?= h($limit) ?> kWh</strong></p>

<div class="card">
    <h3>Today's Meter Status</h3>
    <table>
        <tr>
            <th>Meter</th>
            <th>Today's Reading</th>
            <th>Usage (kWh)</th>
            <th>Status</th>
        </tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= h($r['meter']['name']) ?></td>
            <td><?= $r['today'] ? h($r['today']['reading_value']) . ' kWh' : '<span class="badge badge-over">Not submitted yet</span>' ?></td>
            <td><?= $r['usage'] !== null ? h(number_format($r['usage'], 2)) : '-' ?></td>
            <td>
                <?php if ($r['usage'] === null): ?>
                    -
                <?php elseif ($r['over'] > 0): ?>
                    <span class="badge badge-over">Over by <?= h(number_format($r['over'], 2)) ?> kWh</span>
                <?php else: ?>
                    <span class="badge badge-ok">Within limit</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
        <tr><td colspan="4">No meters yet. Go to Meters to add one.</td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h3>Quick Links</h3>
    <p>
        <a class="btn" href="<?= BASE_URL ?>/meters.php">Manage Meters</a>
        <a class="btn" href="<?= BASE_URL ?>/users.php">Manage Users</a>
        <a class="btn" href="<?= BASE_URL ?>/settings.php">Settings</a>
        <a class="btn" href="<?= BASE_URL ?>/readings.php">All Readings</a>
    </p>
    <p class="hint">Reminder: this dashboard only shows live status. The actual WhatsApp alerts are sent by the scheduled cron scripts (cron/cron_check_usage.php and cron/cron_check_missing.php) - see README.md to set them up in Windows Task Scheduler.</p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
