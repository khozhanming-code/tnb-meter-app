<?php
// ==========================================================
// CRON: Over-Usage WhatsApp Alert
// Run this periodically (e.g. every hour) via Windows Task Scheduler / cron.
// CLI usage:  php cron_check_usage.php
// ==========================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/whatsapp.php';

$today = date('Y-m-d');
$limit = (float) get_setting('daily_usage_limit_kwh', 80);

$meters = get_all_meters(true);

foreach ($meters as $meter) {
    $today_reading = get_reading_for_date($meter['id'], $today);
    if (!$today_reading) {
        continue; // no reading yet today - handled by cron_check_missing.php
    }

    $prev_reading = get_latest_reading($meter['id'], $today);
    if (!$prev_reading) {
        continue; // no previous reading to compare against
    }

    $usage = (float)$today_reading['reading_value'] - (float)$prev_reading['reading_value'];

    if ($usage > $limit) {
        $over_by = $usage - $limit;
        $message = "⚠️ Electricity Usage Alert\n" .
            "Meter: {$meter['name']}\n" .
            "Previous Reading: {$prev_reading['reading_value']} kWh ({$prev_reading['reading_date']})\n" .
            "Current Reading: {$today_reading['reading_value']} kWh ({$today_reading['reading_date']})\n" .
            "Daily Usage: " . number_format($usage, 2) . " kWh\n" .
            "Usage Limit: " . number_format($limit, 2) . " kWh\n" .
            "The electricity usage has exceeded the target by " . number_format($over_by, 2) . " kWh.";

        send_notification_once('over_usage', $meter['id'], $today, $message);
        echo "[{$meter['name']}] Over-usage alert processed.\n";
    } else {
        echo "[{$meter['name']}] Usage within limit ({$usage} kWh).\n";
    }
}
