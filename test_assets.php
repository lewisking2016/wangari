<!DOCTYPE html>
<html>
<head>
    <title>Asset Test - Wangari</title>
    <style>
        body { font-family: system-ui; padding: 40px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .pass { border-left: 4px solid #28a745; }
        .fail { border-left: 4px solid #dc3545; }
        h1 { color: #2c3e50; }
        code { background: #f8f9fa; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔍 Asset Loading Test</h1>
    
    <?php
    $base = '/Frontend/assets/';
    $tests = [
        'GSAP' => $base . 'vendor/gsap/gsap.min.js',
        'Lucide Icons' => $base . 'vendor/lucide/lucide.min.js',
        'Swiper JS' => $base . 'vendor/swiper/swiper-bundle.min.js',
        'Swiper CSS' => $base . 'vendor/swiper/swiper-bundle.min.css',
        'Main CSS' => $base . 'css/style.css',
        'Components CSS' => $base . 'css/components.css',
        'Animations CSS' => $base . 'css/animations.css',
        'Responsive CSS' => $base . 'css/responsive.css',
        'Main JS' => $base . 'js/main.js',
        'Hero Slider JS' => $base . 'js/hero-slider.js',
        'Logo Image' => '/Frontend/images/wangari-logo.svg',
    ];
    
    foreach ($tests as $name => $path) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
        $exists = file_exists($fullPath);
        $class = $exists ? 'pass' : 'fail';
        $status = $exists ? '✓ EXISTS' : '✗ MISSING';
        echo "<div class='test $class'><strong>$status</strong> - $name<br><code>$path</code></div>";
    }
    ?>
    
    <h2>Test Icon Rendering</h2>
    <div class="test">
        <p>If Lucide is working, you should see icons below:</p>
        <div style="display: flex; gap: 20px; margin: 20px 0;">
            <i data-lucide="shopping-cart" style="width: 32px; height: 32px;"></i>
            <i data-lucide="check-circle" style="width: 32px; height: 32px;"></i>
            <i data-lucide="truck" style="width: 32px; height: 32px;"></i>
        </div>
    </div>
    
    <h2>Test Swiper</h2>
    <div class="test">
        <div class="swiper test-swiper" style="width: 100%; height: 200px;">
            <div class="swiper-wrapper">
                <div class="swiper-slide" style="background: #3498db; color: white; display: flex; align-items: center; justify-content: center;">Slide 1</div>
                <div class="swiper-slide" style="background: #e74c3c; color: white; display: flex; align-items: center; justify-content: center;">Slide 2</div>
                <div class="swiper-slide" style="background: #2ecc71; color: white; display: flex; align-items: center; justify-content: center;">Slide 3</div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <p id="swiper-status" style="margin-top: 10px;"></p>
    </div>
    
    <script src="<?php echo $base; ?>vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo $base; ?>vendor/lucide/lucide.min.js"></script>
    <script>
        // Test Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
            console.log('✓ Lucide loaded');
        } else {
            document.write('<p style="color: red;">✗ Lucide NOT loaded</p>');
        }
        
        // Test Swiper
        if (typeof Swiper !== 'undefined') {
            new Swiper('.test-swiper', {
                loop: true,
                autoplay: { delay: 2000 },
                pagination: { el: '.swiper-pagination' }
            });
            document.getElementById('swiper-status').innerHTML = '<span style="color: green;">✓ Swiper is working!</span>';
        } else {
            document.getElementById('swiper-status').innerHTML = '<span style="color: red;">✗ Swiper NOT loaded</span>';
        }
    </script>
</body>
</html>
