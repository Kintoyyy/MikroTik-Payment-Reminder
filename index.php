<?php
$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get selected images
$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Detect device type
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/mobile|android|iphone|ipad|tablet/i', $userAgent);

$imageDir = __DIR__ . '/uploads/';
$image = $isMobile ? $settings['mobile_image'] : $settings['pc_image'];
$imagePath = $image ? $imageDir . $image : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            background: #000;
            overflow: hidden;
        }
        .wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
            background: #000;
        }
        img.responsive-fit {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
        }
        .no-image {
            color: #fff;
            font-size: 1.8rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="wrapper">
<?php if ($image && file_exists($imagePath)): ?>
    <img src="uploads/<?= htmlspecialchars($image) ?>" alt="Reminder" class="responsive-fit">
<?php else: ?>
    <div class="no-image">No image selected yet.</div>
<?php endif; ?>
</div>
</body>
</html>
