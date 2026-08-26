<?php
/**
 * Global Header & Navigation, Wangari
 * Unified light glassmorphism nav — same design as the landing page.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    wangariStartSession();
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

// Determine login state for public site (any logged-in user)
$is_logged_in = !empty($_SESSION['user_id']);
$dashboard_url = '/Frontend/admin/dashboard.php';
if (($_SESSION['role'] ?? '') === 'customer') {
    $dashboard_url = '/Frontend/pages/dashboard.php';
}

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
    <meta name="theme-color" content="#F4FAF5">

    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/swiper/swiper-bundle.min.css">

    <!-- Stylesheets — xai-public.css LAST so nav overrides growvi.css -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/animations.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/growvi.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/xai-public.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/mobile-responsive.css?v=1.1">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <link rel="stylesheet" href="/Frontend/assets/css/mobile-fix.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ -->
<!-- NAVBAR — Unified light glassmorphism           -->
<!-- ═══════════════════════════════════════════════ -->
<nav class="xai-nav" id="mainNav">
    <div class="xai-nav-inner">
        <a href="/" class="xai-nav-brand">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            Wangari<span>.</span>
        </a>

        <ul class="xai-nav-links">
            <li><a class="<?php echo navActive('home', $currentPage); ?>" href="/">Home</a></li>
            <li><a class="<?php echo navActive('about', $currentPage); ?>" href="/Frontend/pages/about.php">About</a></li>
            <li><a class="<?php echo navActive('services', $currentPage); ?>" href="/Frontend/pages/services.php">Services</a></li>
            <li><a class="<?php echo navActive('pricing', $currentPage); ?>" href="/Frontend/pages/pricing.php">Pricing</a></li>
            <li><a class="<?php echo navActive('contact', $currentPage); ?>" href="/Frontend/pages/contact.php">Contact</a></li>
        </ul>

        <div class="xai-nav-actions">
            <?php if ($is_logged_in): ?>
                <a href="<?php echo $dashboard_url; ?>" class="xai-nav-ghost">Dashboard</a>
                <a href="/Frontend/pages/logout.php" class="xai-nav-cta" style="background: #fee2e2; color: #b91c1c; border-color: #fecaca;">Sign Out</a>
            <?php else: ?>
                <a href="/Frontend/pages/login.php" class="xai-nav-ghost">Sign In</a>
                <a href="/Frontend/pages/register.php" class="xai-nav-cta">Get Started</a>
            <?php endif; ?>
            <button class="xai-mobile-toggle" id="mobileMenuBtn" aria-label="Open menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Menu Drawer -->
<div id="mobileMenu" class="xai-mobile-menu" role="dialog" aria-modal="true" aria-label="Navigation menu">
    <div class="xai-mobile-menu-header">
        <a href="/" class="xai-mobile-menu-brand">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            Wangari<span>.</span>
        </a>
        <button class="xai-mobile-close" id="mobileMenuClose" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="xai-mobile-menu-links">
        <a href="/">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a>
        <a href="/Frontend/pages/about.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            About
        </a>
        <a href="/Frontend/pages/services.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            Services
        </a>
        <a href="/Frontend/pages/pricing.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Pricing
        </a>
        <a href="/Frontend/pages/contact.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            Contact
        </a>
    </div>

    <div class="xai-mobile-menu-footer">
        <?php if ($is_logged_in): ?>
            <a href="<?php echo $dashboard_url; ?>" class="xai-m-signin">Go to Dashboard</a>
            <a href="/Frontend/pages/logout.php" class="xai-m-cta" style="background: #fee2e2; color: #b91c1c;">Sign Out</a>
        <?php else: ?>
            <a href="/Frontend/pages/login.php" class="xai-m-signin">Sign In</a>
            <a href="/Frontend/pages/register.php" class="xai-m-cta">Get Started Free →</a>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var nav = document.getElementById('mainNav');
    var mobileMenu = document.getElementById('mobileMenu');
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var mobileMenuClose = document.getElementById('mobileMenuClose');

    // Scroll effect
    function onScroll() {
        if (nav && window.scrollY > 50) { nav.classList.add('scrolled'); }
        else if (nav) { nav.classList.remove('scrolled'); }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Mobile menu
    function openMenu() {
        if (mobileMenu) { mobileMenu.classList.add('open'); document.body.style.overflow = 'hidden'; }
    }
    function closeMenu() {
        if (mobileMenu) { mobileMenu.classList.remove('open'); document.body.style.overflow = ''; }
    }
    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMenu);
    if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMenu);
    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach(function(link) { link.addEventListener('click', closeMenu); });
        mobileMenu.addEventListener('click', function(e) { if (e.target === mobileMenu) closeMenu(); });
    }
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeMenu(); });
})();
</script>
