<?php
/**
 * Global Header & Navigation, Wangari
 * Growvi-style dark ink navigation with lime accents.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/config.php';
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}

if (!isset($page_title)) {
    $page_title = SITE_NAME . ' - ' . SITE_TAGLINE;
}

$currentPage = basename($_SERVER['REQUEST_URI'] ?? '', '.php');
$currentPage = rtrim($currentPage, '/');
if ($currentPage === '' || $currentPage === 'index') {
    $currentPage = 'home';
}

function navActive(string $page, string $current): string {
    return ($page === $current) ? ' active' : '';
}

// Determine login state for public site (only customer role shows on website)
$is_customer_logged_in = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';

// Cart count for badge
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum(array_map('intval', $_SESSION['cart']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SEO -->
    <meta name="description" content="Wangari, smart farming for a sustainable future. Track poultry, livestock, crops, feed production, sales and finances in one platform. Inspired by Prof. Wangari Maathai.">
    <meta name="theme-color" content="#000B22">

    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Google Fonts: Inter Tight + Instrument Serif (Growvi type system) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/swiper/swiper-bundle.min.css">

    <!-- Stylesheets (growvi.css loaded last to override legacy) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/animations.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/growvi.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
</head>
<body>

<!-- ═══════════════════════════════════════════════ -->
<!-- NAVBAR, Growvi ink style                       -->
<!-- ═══════════════════════════════════════════════ -->
<nav class="g-nav" id="gNav">
    <div class="g-nav-inner">
        <a href="/" class="g-logo">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            <span>Wangari<em>.</em></span>
        </a>

        <ul class="g-nav-links" id="gNavLinks">
            <li><a class="<?php echo navActive('home', $currentPage); ?>" href="/">Home</a></li>
            <li><a class="<?php echo navActive('about', $currentPage); ?>" href="/Frontend/pages/about.php">About</a></li>
            <li><a class="<?php echo navActive('services', $currentPage); ?>" href="/Frontend/pages/services.php">Services</a></li>
            <li><a class="<?php echo navActive('pricing', $currentPage); ?>" href="/Frontend/pages/pricing.php">Pricing</a></li>
            <li><a class="<?php echo navActive('contact', $currentPage); ?>" href="/Frontend/pages/contact.php">Contact</a></li>
            <li><a class="<?php echo navActive('download', $currentPage); ?>" href="/Frontend/pages/download.php" style="color: var(--xai-lime, #4ADE80);">Download App</a></li>
        </ul>

        <div class="g-nav-right">
            <?php if ($is_customer_logged_in): ?>
                <a class="g-btn g-btn-lime g-nav-cta" href="/Frontend/pages/logout.php">Sign Out</a>
            <?php else: ?>
                <a class="g-btn g-btn-outline g-nav-cta" href="/Frontend/pages/login.php">Login</a>
                <a class="g-btn g-btn-lime g-nav-cta" href="/Frontend/pages/register.php">Get Started</a>
            <?php endif; ?>

            <button class="g-hamburger" id="gHamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<script>
(function () {
    var nav = document.getElementById('gNav');
    var links = document.getElementById('gNavLinks');
    var burger = document.getElementById('gHamburger');

    function onScroll() {
        if (nav && window.scrollY > 30) { nav.classList.add('scrolled'); }
        else if (nav) { nav.classList.remove('scrolled'); }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (burger && links) {
        burger.addEventListener('click', function () {
            burger.classList.toggle('open');
            links.classList.toggle('open');
        });
    }
})();
</script>
