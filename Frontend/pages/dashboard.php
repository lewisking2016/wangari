<?php
/**
 * Wangari — Customer Dashboard
 * Landing page after login for farm_manager and team roles.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$role     = $_SESSION['role'] ?? 'farm_manager';
$fullName = $_SESSION['full_name'] ?? $username;
$email    = $_SESSION['email'] ?? '';
$firstName = explode(' ', $fullName)[0] ?? $username;

$page_title = 'Dashboard — Wangari';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        .dash { max-width: 1200px; margin: 0 auto; padding: 100px 24px 60px; }
        .dash-welcome { margin-bottom: 40px; }
        .dash-welcome h1 { font-size: 2rem; font-weight: 800; color: var(--xai-text); margin-bottom: 8px; }
        .dash-welcome p { color: var(--xai-text-secondary); font-size: 1.05rem; }
        .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .dash-card {
            background: var(--xai-card-bg);
            border: 1.5px solid var(--xai-border);
            border-radius: 16px;
            padding: 28px 24px;
            transition: all 0.25s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .dash-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.08);
            border-color: var(--xai-lime);
        }
        @media (max-width: 640px) {
            .dash { padding: 80px 16px 40px; }
            .dash-welcome h1 { font-size: 1.5rem; }
            .dash-grid { grid-template-columns: 1fr; }
            .dash-quick { flex-direction: column; }
            .dash-quick-btn { width: 100%; justify-content: center; }
            .xai-nav-links { display: none; }
        }
        .dash-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dash-card h3 { font-size: 1.1rem; font-weight: 700; color: var(--xai-text); }
        .dash-card p { font-size: 0.88rem; color: var(--xai-text-secondary); line-height: 1.5; margin: 0; }
        .dash-card-arrow { margin-top: auto; color: var(--xai-lime); font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; }
        .dash-card-arrow svg { transition: transform 0.2s; }
        .dash-card:hover .dash-card-arrow svg { transform: translateX(4px); }
        .dash-quick { display: flex; gap: 12px; margin-bottom: 32px; flex-wrap: wrap; }
        .dash-quick-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--xai-surface);
            border: 1px solid var(--xai-border);
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--xai-text);
            text-decoration: none;
            transition: all 0.2s;
        }
        .dash-quick-btn:hover { background: rgba(34,197,94,0.08); border-color: var(--xai-lime); color: var(--xai-green); }
        .dash-quick-btn svg { color: var(--xai-lime); }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="xai-nav scrolled" id="mainNav">
    <div class="xai-nav-inner">
        <a href="/" class="xai-nav-brand">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            Wangari<span>.</span>
        </a>
        <ul class="xai-nav-links">
            <li><a href="/Frontend/pages/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
            <li><a href="/Frontend/pages/about.php">About</a></li>
        </ul>
        <div class="xai-nav-actions">
            <a href="/Frontend/pages/logout.php" class="xai-nav-ghost">Sign Out</a>
            <button class="xai-mobile-toggle" id="mobileMenuBtn" aria-label="Open menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</nav>

<div class="dash">
    <div class="dash-welcome">
        <h1>Welcome back, <?php echo htmlspecialchars($firstName); ?></h1>
        <p>Here's your farm at a glance. Choose a module to get started.</p>
    </div>

    <!-- Quick Actions -->
    <div class="dash-quick">
        <a href="/Frontend/pages/pricing.php" class="dash-quick-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Upgrade Plan
        </a>
        <a href="/Frontend/pages/contact.php" class="dash-quick-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            Contact Support
        </a>
    </div>

    <!-- Module Cards -->
    <div class="dash-grid">
        <a href="/Frontend/admin/dashboard.php" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(34,197,94,0.1); color: #22C55E;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <h3>Farm Operations</h3>
            <p>Manage flocks, herds, production records, health tracking and daily entries.</p>
            <div class="dash-card-arrow">Open Module <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
        </a>

        <a href="/Frontend/admin/dashboard.php" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(245,158,11,0.1); color: #F59E0B;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <h3>Inventory & Feed</h3>
            <p>Track raw materials, feed formulas, stock levels and low-stock alerts.</p>
            <div class="dash-card-arrow">Open Module <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
        </a>

        <a href="/Frontend/admin/dashboard.php" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(59,130,246,0.1); color: #3B82F6;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3>Finance & Sales</h3>
            <p>Manage orders, invoices, customer credit, cashbook and profit reports.</p>
            <div class="dash-card-arrow">Open Module <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
        </a>

        <a href="/Frontend/admin/dashboard.php" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(168,85,247,0.1); color: #A855F7;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <h3>Reports & AI</h3>
            <p>View analytics, trend charts, AI insights and exportable reports.</p>
            <div class="dash-card-arrow">Open Module <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
        </a>
    </div>
</div>

<script>
// Mobile menu
var nav = document.getElementById('mainNav');
var btn = document.getElementById('mobileMenuBtn');
if (btn) btn.addEventListener('click', function() { window.location.href = '/Frontend/pages/login.php'; });
</script>
</body>
</html>
