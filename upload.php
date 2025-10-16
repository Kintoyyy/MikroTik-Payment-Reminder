<?php
require 'auth.php';
require_auth();

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure settings table exists
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY,
    display_mode TEXT DEFAULT 'carousel',
    carousel_duration INTEGER DEFAULT 5
)");

// Ensure image_flags table exists
$db->exec("CREATE TABLE IF NOT EXISTS image_flags (
    filename TEXT PRIMARY KEY,
    pc_enabled INTEGER DEFAULT 0,
    mobile_enabled INTEGER DEFAULT 0
)");

// Force mode to carousel
$db->exec("UPDATE settings SET display_mode = 'carousel'");

function random_filename($ext) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6) . '.' . $ext;
}

$host = $_SERVER['HTTP_HOST'] ?? gethostname();

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $mime = mime_content_type($file['tmp_name']);
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($ext, $allowed) || !in_array($mime, $allowedMime)) {
            $message = "❌ Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
        } else {
            $filename = random_filename($ext);
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $stmt = $db->prepare("INSERT INTO image_flags (filename) VALUES (?)");
            $stmt->execute([$filename]);
            $message = "✅ Uploaded: " . htmlspecialchars($filename);
        }
    } else {
        $message = "⚠️ Upload failed.";
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $filename = basename($_POST['delete']);
    $target = $uploadDir . $filename;
    if (is_file($target)) unlink($target);
    $db->prepare("DELETE FROM image_flags WHERE filename = ?")->execute([$filename]);
    $message = "🗑️ Deleted: " . htmlspecialchars($filename);
}

// Handle PC/Mobile toggle (multi-select)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_type'], $_POST['image_name'])) {
    $type = $_POST['toggle_type'];
    // Checkbox only sends "on" if checked — undefined otherwise
    $flag = (!empty($_POST['toggle_state']) && $_POST['toggle_state'] === 'on') ? 1 : 0;

    $stmt = $db->prepare("INSERT INTO image_flags (filename, {$type}_enabled)
                          VALUES (?, ?)
                          ON CONFLICT(filename) DO UPDATE SET {$type}_enabled = ?");
    $stmt->execute([$_POST['image_name'], $flag, $flag]);

    $message = $flag ? "✅ Enabled for $type" : "❎ Disabled for $type";
}


// Carousel duration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['carousel_duration'])) {
    $duration = max(1, intval($_POST['carousel_duration']));
    $db->prepare("UPDATE settings SET carousel_duration = ?")->execute([$duration]);
    $message = "✅ Duration set to {$duration}s.";
}

// Password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_pass'], $_POST['new_pass'], $_POST['confirm_pass'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $old = $_POST['old_pass']; $new = $_POST['new_pass']; $confirm = $_POST['confirm_pass'];

    if (!$user || !password_verify($old, $user['password'])) $message = "❌ Incorrect password.";
    elseif ($new !== $confirm) $message = "⚠️ Passwords do not match.";
    elseif (strlen($new) < 6) $message = "⚠️ Too short.";
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ? WHERE username = ?")->execute([$hash, $_SERVER['PHP_AUTH_USER']]);
        $message = "✅ Password changed.";
    }
}

// Load
$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$images = array_values(array_diff(scandir($uploadDir), ['.', '..']));
$flags = $db->query("SELECT * FROM image_flags")->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder Seetings</title>
    <link href="./src/bootstrap.min.css" rel="stylesheet">
    <script src="./src/bootstrap.bundle.min.js"></script>
    <style>
        .form-switch .form-check-input { cursor: pointer; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Payment Reminder Hosting</h2>
        <span><a href="https://kintoyyy.com" class="badge bg-secondary">Created by Kintoyyy</a></span>
        
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Upload -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5>Upload New Image</h5>
            <form method="POST" enctype="multipart/form-data" class="mt-3">
                <div class="input-group">
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" class="form-control" required>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
                <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF</small>
            </form>
        </div>
    </div>

    <!-- Carousel Duration -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5>Carousel Duration</h5>
            <form method="POST" class="mt-3 d-flex align-items-center">
                <input type="number" name="carousel_duration" value="<?= htmlspecialchars($settings['carousel_duration']) ?>" min="1" class="form-control form-control-sm me-2" style="width: 90px;">
                <span class="text-muted me-3">seconds per slide</span>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
            </form>
        </div>
    </div>

    <!-- Hosted Images -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5>Hosted Images</h5>
            <?php if ($images): ?>
            <table class="table table-bordered table-hover mt-3 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Preview</th>
                        <th>Filename</th>
                        <th class="text-center">PC</th>
                        <th class="text-center">Mobile</th>
                        <th>Link</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $img): 
                        $f = $flags[$img] ?? ['pc_enabled'=>0,'mobile_enabled'=>0];
                    ?>
                    <tr>
                        <td><img src="uploads/<?= htmlspecialchars($img) ?>" class="img-thumbnail" style="max-width: 100px;"></td>
                        <td><?= htmlspecialchars($img) ?></td>
                        <td class="text-center">
                            <form method="POST">
                                <input type="hidden" name="toggle_type" value="pc">
                                <input type="hidden" name="image_name" value="<?= htmlspecialchars($img) ?>">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="toggle_state"
                                        <?= $f['pc_enabled'] ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                </div>
                            </form>
                        </td>
                        <td class="text-center">
                            <form method="POST">
                                <input type="hidden" name="toggle_type" value="mobile">
                                <input type="hidden" name="image_name" value="<?= htmlspecialchars($img) ?>">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="toggle_state"
                                        <?= $f['mobile_enabled'] ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                </div>
                            </form>
                        </td>
                        <td><a href="http://<?= htmlspecialchars($host) ?>/uploads/<?= htmlspecialchars($img) ?>" target="_blank" class="small text-decoration-none">View</a></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="delete" value="<?= htmlspecialchars($img) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p class="text-muted mt-3">No images uploaded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5>Change Password</h5>
            <form method="POST" class="mt-3">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="old_pass" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_pass" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_pass" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">Change Password</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
