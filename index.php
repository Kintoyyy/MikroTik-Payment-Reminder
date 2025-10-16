<?php
$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$displayMode = $settings['display_mode'] ?? 'single';

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/mobile|android|iphone|ipad|tablet/i', $userAgent);

$imageDir = __DIR__ . '/uploads/';
$images = array_values(array_diff(scandir($imageDir), ['.', '..']));

$selected = $isMobile ? $settings['mobile_image'] : $settings['pc_image'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder</title>
    <link href="./src/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #000;
            overflow: hidden;
            height: 100%;
            width: 100%;
        }
        .carousel-item img, .single-image {
            width: 100%;
            height: 100vh;
            object-fit: contain;
            background: #000;
        }
    </style>
</head>
<body>
<?php if ($displayMode === 'carousel' && $images): ?>
    <div id="imageCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($images as $index => $img): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Slide">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="./src/bootstrap.bundle.min.js"></script>
    <script>
        const carousel = new bootstrap.Carousel('#imageCarousel', {
            interval: <?= ($settings['carousel_duration'] ?? 5) * 1000 ?>,
            ride: 'carousel',
            pause: false
        });
    </script>
<?php elseif ($selected && file_exists($imageDir . $selected)): ?>
    <img src="uploads/<?= htmlspecialchars($selected) ?>" class="single-image" alt="Reminder">
<?php else: ?>
    <div class="text-light fs-3 text-center" style="margin-top:20%;">No image selected yet.</div>
<?php endif; ?>
</body>
</html>
