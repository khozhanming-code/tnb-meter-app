<?php
// ==========================================================
// CRON: Missing Daily Meter Reading WhatsApp Reminder
// Run this periodically (e.g. every 30 minutes) via Windows Task Scheduler / cron.
// It only actually sends once the current Malaysia time has passed the configured
// submission deadline for that day.
// CLI usage:  php cron_check_missing.php
// ==========================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/whatsapp.php';

$today = date('Y-m-d');
$now = date('H:i');
$deadline = get_setting('submission_deadline_time', '10:00');

if ($now < $deadline) {
    echo "Before deadline ($deadline). No check performed yet.\n";
    exit;
}

$meters = get_all_meters(true);
$readable_date = date('d/F/Y', strtotime($today));

foreach ($meters as $meter) {
    $today_reading = get_reading_for_date($meter['id'], $today);

    if (!$today_reading) {
        $message = "🔔 Missing Meter Reading Reminder\n" .
            "Meter: {$meter['name']}\n" .
            "No meter reading found on {$readable_date}.\n" .
            "Submission deadline ({$deadline}) has passed.";

        send_notification_once('missing_reading', $meter['id'], $today, $message);
        echo "[{$meter['name']}] Missing reading reminder processed.\n";
    } else {
        echo "[{$meter['name']}] Reading already submitted for today.\n";
    }
}
