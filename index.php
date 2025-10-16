<?php
$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$duration = max(1, intval($settings['carousel_duration'] ?? 5)) * 1000;

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/Mobile|Android|iPhone|iPad|Opera Mini|IEMobile|WPDesktop/i', $userAgent);

$type = $isMobile ? 'mobile_enabled' : 'pc_enabled';
$stmt = $db->prepare("SELECT filename FROM image_flags WHERE $type = 1 ORDER BY filename ASC");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
            position: relative;
        }
        .carousel-item {
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .carousel-item::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: inherit;
            background-size: cover;
            background-position: center;
            filter: blur(4px) brightness(0.2);
            transform: scale(1.1);
            z-index: 0;
        }

        .carousel-item::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35);
            z-index: 1;
        }

        .carousel-item img {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.7));
        }
        
        .carousel-fade .carousel-item {
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .carousel-fade .carousel-item.active {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="<?= $duration ?>">
        <div class="carousel-inner">
            <?php foreach ($images as $i => $img): ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" 
                     style="background-image: url('uploads/<?= htmlspecialchars($img) ?>');">
                    <img src="uploads/<?= htmlspecialchars($img) ?>" alt="Slide Image">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>