<?php
$imageDir = __DIR__ . '/uploads/';
$files = glob($imageDir . '*');
usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));
$latestImage = $files ? basename(end($files)) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 0; background: #000; text-align: center; }
        img { width: 100%; height: auto; display: block; }
    </style>
</head>
<body>
<?php if ($latestImage): ?>
    <img src="uploads/<?= htmlspecialchars($latestImage) ?>" alt="Uploaded Image">
<?php else: ?>
    <div class="text-light fs-3" style="margin-top:20%;">No image uploaded yet.</div>
<?php endif; ?>
</body>
</html>
