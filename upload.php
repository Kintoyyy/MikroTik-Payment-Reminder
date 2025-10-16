<?php
require 'auth.php';
require_auth();

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function random_filename($ext) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6) . '.' . $ext;
}

$host = $_SERVER['HTTP_HOST'] ?? gethostname();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = random_filename($ext);
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
        $message = "✅ Image uploaded successfully: " . htmlspecialchars($filename);
    } else {
        $message = "⚠️ Upload failed.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $filename = basename($_POST['delete']);
    $target = $uploadDir . $filename;
    if (is_file($target)) {
        unlink($target);
        $message = "🗑️ Deleted image: " . htmlspecialchars($filename);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_pass'], $_POST['new_pass'], $_POST['confirm_pass'])) {
    $old = $_POST['old_pass'];
    $new = $_POST['new_pass'];
    $confirm = $_POST['confirm_pass'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($old, $user['password'])) {
        $message = "❌ Incorrect current password.";
    } elseif ($new !== $confirm) {
        $message = "⚠️ New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $message = "⚠️ Password must be at least 6 characters.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
        $update->execute([$hash, $_SERVER['PHP_AUTH_USER']]);
        $message = "✅ Password changed successfully.";
    }
}

$images = array_values(array_diff(scandir($uploadDir), ['.', '..']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder - Image Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4 text-center">Payment Reminder</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5>Upload New Image</h5>
            <form method="POST" enctype="multipart/form-data" class="mt-3">
                <div class="input-group">
                    <input type="file" name="image" class="form-control" required>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5>Hosted Images</h5>
            <?php if ($images): ?>
                <table class="table table-bordered table-hover mt-3">
                    <thead class="table-light">
                        <tr>
                            <th>Preview</th>
                            <th>Filename</th>
                            <th>Direct Link</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($images as $img): ?>
                            <tr>
                                <td><img src="uploads/<?= htmlspecialchars($img) ?>" class="img-thumbnail" style="max-width: 100px;"></td>
                                <td><?= htmlspecialchars($img) ?></td>
                                <td>
                                    <a href="http://<?= htmlspecialchars($host) ?>/uploads/<?= htmlspecialchars($img) ?>" 
                                       target="_blank" 
                                       class="text-decoration-none">
                                       http://<?= htmlspecialchars($host) ?>/uploads/<?= htmlspecialchars($img) ?>
                                    </a>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this image?');" class="d-inline">
                                        <input type="hidden" name="delete" value="<?= htmlspecialchars($img) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
