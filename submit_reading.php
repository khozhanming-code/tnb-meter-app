<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

global $pdo;
$user = current_user();
$version = app_version(); // '1' or '2'
$error = '';
$success = '';

$meters = get_meters_for_user($user['id'], $user['role']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session, please refresh and try again.';
    } else {
        $meter_id = (int)($_POST['meter_id'] ?? 0);
        $reading_value = trim($_POST['reading_value'] ?? '');

        if (!$meter_id || !user_can_access_meter($user['id'], $user['role'], $meter_id)) {
            $error = 'Please select a valid meter.';
        } elseif ($reading_value === '' || !is_numeric($reading_value)) {
            $error = 'Please enter a valid numeric meter reading.';
        } else {
            $photo_filename = false;

            if ($version === '2') {
                // Version 2: ONLY accept the live-captured base64 photo. No file upload field is used at all.
                $photo_data = $_POST['photo_data'] ?? '';
                if (empty($photo_data)) {
                    $error = 'A live meter photo is required. Please capture a photo using the camera before submitting.';
                } else {
                    $photo_filename = save_base64_photo($photo_data, 'meter_v2');
                    if (!$photo_filename) {
                        $error = 'Failed to save the captured photo. Please try capturing again.';
                    }
                }
            } else {
                // Version 1: normal file upload (camera or gallery, browser's choice)
                if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Please take or upload a photo of the meter.';
                } else {
                    $photo_filename = save_uploaded_photo($_FILES['photo'], 'meter_v1');
                    if (!$photo_filename) {
                        $error = 'Failed to upload photo. Allowed formats: JPG, PNG, WEBP.';
                    }
                }
            }

            if (!$error && $photo_filename) {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO meter_readings
                         (meter_id, user_id, reading_value, photo_path, capture_method, reading_date, reading_time)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $meter_id,
                        $user['id'],
                        $reading_value,
                        $photo_filename,
                        $version === '2' ? 'v2_live_camera' : 'v1_upload',
                        date('Y-m-d'),
                        date('H:i:s'),
                    ]);
                    $success = 'Meter reading submitted successfully at ' . date('d/M/Y h:i A') . ' (Malaysia time).';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'A reading for this meter has already been submitted today.';
                    } else {
                        $error = 'Failed to save reading: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$page_title = 'Submit Reading';
include __DIR__ . '/includes/header.php';
?>
<h1>Submit Meter Reading</h1>
<p class="hint">Mode: <strong><?= $version === '2' ? 'Version 2 - Live Camera Capture Required' : 'Version 1 - Photo upload allowed' ?></strong></p>

<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

<div class="card">
<?php if (empty($meters)): ?>
    <p>No meter has been assigned to you yet. Please contact the Admin.</p>
<?php else: ?>

    <form method="post" enctype="multipart/form-data" id="readingForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label>Select Meter / Building</label>
            <select name="meter_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($meters as $m): ?>
                <option value="<?= $m['id'] ?>"><?= h($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($version === '2'): ?>
            <!-- ===================== VERSION 2: LIVE CAMERA ONLY ===================== -->
            <div class="form-group">
                <label>Live Meter Photo (camera capture required)</label>
                <video id="cameraStream" class="camera-stream" autoplay playsinline></video>
                <canvas id="captureCanvas" style="display:none;"></canvas>
                <img id="capturedPreview" class="meter-photo-preview" alt="Captured meter photo">
                <div style="margin-top:10px;">
                    <button type="button" id="startCameraBtn" class="btn btn-secondary">Start Camera</button>
                    <button type="button" id="captureBtn" class="btn" disabled>Capture Photo</button>
                    <button type="button" id="retakeBtn" class="btn btn-secondary" style="display:none;">Retake</button>
                </div>
                <p class="hint">No gallery/file picker is offered in Version 2 - the photo must be taken live, right now, using the camera above.</p>
                <input type="hidden" name="photo_data" id="photoData">
            </div>
        <?php else: ?>
            <!-- ===================== VERSION 1: NORMAL UPLOAD ===================== -->
            <div class="form-group">
                <label>Meter Photo (take a photo or choose from gallery)</label>
                <input type="file" name="photo" accept="image/*" capture="environment" required>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Meter Reading (kWh)</label>
            <input type="number" step="0.01" name="reading_value" required placeholder="e.g. 6100">
        </div>

        <button type="submit" id="submitBtn" <?= $version === '2' ? 'disabled' : '' ?>>Submit Reading</button>
    </form>

<?php endif; ?>
</div>

<?php if ($version === '2'): ?>
<script src="<?= BASE_URL ?>/assets/js/camera.js"></script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
