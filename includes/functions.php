<?php
require_once __DIR__ . '/../config/db.php';

// ---------- Settings ----------
function get_setting($key, $default = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function set_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([$key, $value]);
}

function app_version() {
    return get_setting('app_version', '1');
}

// ---------- Meters ----------
function get_all_meters($active_only = true) {
    global $pdo;
    $sql = "SELECT * FROM meters";
    if ($active_only) $sql .= " WHERE status = 'active'";
    $sql .= " ORDER BY name";
    return $pdo->query($sql)->fetchAll();
}

function get_meters_for_user($user_id, $role) {
    global $pdo;
    if ($role === 'admin') {
        return get_all_meters(true);
    }
    $stmt = $pdo->prepare(
        "SELECT m.* FROM meters m
         JOIN user_meters um ON um.meter_id = m.id
         WHERE um.user_id = ? AND m.status = 'active'
         ORDER BY m.name"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function user_can_access_meter($user_id, $role, $meter_id) {
    if ($role === 'admin') return true;
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM user_meters WHERE user_id = ? AND meter_id = ?");
    $stmt->execute([$user_id, $meter_id]);
    return (bool)$stmt->fetch();
}

// ---------- Readings ----------
function get_latest_reading($meter_id, $before_date = null) {
    global $pdo;
    $sql = "SELECT * FROM meter_readings WHERE meter_id = ?";
    $params = [$meter_id];
    if ($before_date) {
        $sql .= " AND reading_date < ?";
        $params[] = $before_date;
    }
    $sql .= " ORDER BY reading_date DESC, reading_time DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function get_reading_for_date($meter_id, $date) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM meter_readings WHERE meter_id = ? AND reading_date = ?");
    $stmt->execute([$meter_id, $date]);
    return $stmt->fetch();
}

// ---------- Misc ----------
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function save_base64_photo($base64_data, $prefix) {
    // Expects data like "data:image/jpeg;base64,....."
    if (!preg_match('/^data:image\/(\w+);base64,/', $base64_data, $type)) {
        return false;
    }
    $ext = strtolower($type[1]) === 'jpeg' ? 'jpg' : strtolower($type[1]);
    $data = substr($base64_data, strpos($base64_data, ',') + 1);
    $data = base64_decode($data);
    if ($data === false) return false;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    if (file_put_contents($filepath, $data) === false) return false;

    return $filename;
}

function save_uploaded_photo($file_array, $prefix) {
    if (!isset($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) return false;

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $mime = mime_content_type($file_array['tmp_name']);
    if (!in_array($mime, $allowed)) return false;

    $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file_array['tmp_name'], $filepath)) return false;

    return $filename;
}
