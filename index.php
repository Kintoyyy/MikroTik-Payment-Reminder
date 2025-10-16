<?php
$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Load settings
$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$duration = max(1, intval($settings['carousel_duration'] ?? 5)) * 1000;

// Determine device type
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/Mobile|Android|iPhone|iPad|Opera Mini|IEMobile|WPDesktop/i', $userAgent);

// Get enabled images for this device
$type = $isMobile ? 'mobile_enabled' : 'pc_enabled';
$stmt = $db->prepare("SELECT filename FROM image_flags WHERE $type = 1 ORDER BY filename ASC");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If no images, show message
if (!$images) {
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1'>
          <title>No Images</title>
          <style>
            body { display:flex; justify-content:center; align-items:center; height:100vh; background:#111; color:#bbb; font-family:sans-serif; }
          </style></head><body>
          <p>No images have been enabled for this device.</p></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="./src/bootstrap.min.css" rel="stylesheet">
    <script src="./src/bootstrap.bundle.min.js"></script>
    <style>
        html, body {
            margin: 0;
            height: 100%;
            background: #000;
            overflow: hidden;
        }
        .carousel, .carousel-inner, .carousel-item {
            height: 100vh;
        }
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* show entire image without cropping */
            background: #000;
        }
        .carousel-fade .carousel-item {
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .carousel-fade .carousel-item.active {
            opacity: 1;
        }
        /* Hide default indicators and controls */
        .carousel-control-prev,
        .carousel-control-next,
        .carousel-indicators {
            display: none;
        }
    </style>
</head>
<body>
    <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="<?= $duration ?>">
        <div class="carousel-inner">
            <?php foreach ($images as $i => $img): ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <img src="uploads/<?= htmlspecialchars($img) ?>" alt="">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
