<?php
require_once __DIR__ . '/functions.php';

/**
 * Sends a WhatsApp message to the Admin number configured in Settings.
 *
 * This is written against Fonnte's simple HTTP API (https://fonnte.com) because
 * it is one of the easiest WhatsApp gateways to set up for a trial (no Meta Business
 * verification needed). You can swap this out for Twilio WhatsApp API, WABLAS,
 * or the official WhatsApp Cloud API later - only this function needs to change,
 * nothing else in the app calls the HTTP API directly.
 *
 * Settings used (edit them in Admin > Settings, or directly in the `settings` table):
 *   whatsapp_api_url    - the gateway endpoint
 *   whatsapp_api_token  - your gateway auth token
 *   whatsapp_admin_number - Admin's WhatsApp number, e.g. 60123456789 (no +, no leading 0)
 *
 * @return bool true if the gateway accepted the request
 */
function send_whatsapp_message($message) {
    $api_url = get_setting('whatsapp_api_url');
    $token   = get_setting('whatsapp_api_token');
    $number  = get_setting('whatsapp_admin_number');

    if (!$api_url || !$token || !$number) {
        error_log('WhatsApp not sent - missing settings (api_url/token/number).');
        return false;
    }

    // ---- Fonnte-style payload. Adjust field names if you use a different gateway. ----
    $payload = [
        'target'  => $number,
        'message' => $message,
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        error_log('WhatsApp send cURL error: ' . $curl_err);
        return false;
    }

    // Consider 2xx a success; log the raw response either way for debugging.
    error_log('WhatsApp API response (' . $http_code . '): ' . $response);
    return $http_code >= 200 && $http_code < 300;
}

/**
 * Logs a notification and sends it, but only once per (type, meter, date) -
 * prevents duplicate WhatsApp spam if the cron job runs multiple times a day.
 */
function send_notification_once($type, $meter_id, $log_date, $message) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT id FROM notification_log WHERE type = ? AND meter_id = ? AND log_date = ?"
    );
    $stmt->execute([$type, $meter_id, $log_date]);
    if ($stmt->fetch()) {
        return; // already sent today for this meter
    }

    $sent = send_whatsapp_message($message);

    $stmt = $pdo->prepare(
        "INSERT INTO notification_log (type, meter_id, log_date, message, status)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$type, $meter_id, $log_date, $message, $sent ? 'sent' : 'failed']);
}
