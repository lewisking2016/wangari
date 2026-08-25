<?php
/**
 * Wangari Landing Page - x.ai/bot inspired design
 */
declare(strict_types=1);

$page_title = 'Wangari - Smart Farming for a Sustainable Future';

// Start session and check login state
require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    wangariStartSession();
}
$is_logged_in = !empty($_SESSION['user_id']);
$dashboard_url = '/Frontend/admin/dashboard.php';
if (($_SESSION['role'] ?? '') === 'customer') {
    $dashboard_url = '/Frontend/pages/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-sections.css">

    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
</head>
<body>

<!-- Navigation -->
<nav class="xai-nav" id="mainNav">
    <div class="xai-nav-inner">
        <a href="/" class="xai-nav-brand">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            Wangari<span>.</span>
        </a>
        <ul class="xai-nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/Frontend/pages/about.php">About</a></li>
            <li><a href="/Frontend/pages/services.php">Services</a></li>
            <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
            <li><a href="/Frontend/pages/contact.php">Contact</a></li>
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

    <!-- Drawer header: logo + close -->
    <div class="xai-mobile-menu-header">
        <a href="/" class="xai-mobile-menu-brand">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            Wangari<span>.</span>
        </a>
        <button class="xai-mobile-close" id="mobileMenuClose" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Nav links -->
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

    <!-- Footer CTAs -->
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

<main>

    <!-- Hero Section -->
    <section class="xai-hero">
        <div class="xai-hero-content xai-reveal">
            <div class="xai-hero-eyebrow xai-icon-animated">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Built for real farm teams
            </div>
            <h1>One System.<br>Every Farm.<br><span>Smart Farming Technology</span></h1>
            <p class="xai-hero-sub">Wangari keeps your farm records, feed production, sales, and finances in one platform that works on any device.</p>
            <div class="xai-hero-actions">
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo $dashboard_url; ?>" class="xai-btn xai-btn-primary xai-btn-lg">
                        Go to Dashboard
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </a>
                    <a href="/Frontend/pages/logout.php" class="xai-btn xai-btn-secondary xai-btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        Sign Out
                    </a>
                <?php else: ?>
                    <a href="/Frontend/pages/register.php" class="xai-btn xai-btn-primary xai-btn-lg">
                        Get Started Free
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="/Frontend/pages/login.php" class="xai-btn xai-btn-secondary xai-btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
            <div class="xai-hero-stats">
                <div class="xai-stat">
                    <div class="xai-stat-value">12+</div>
                    <div class="xai-stat-label">Integrated Modules</div>
                </div>
                <div class="xai-stat">
                    <div class="xai-stat-value">7</div>
                    <div class="xai-stat-label">Hubs: Farm to Finance</div>
                </div>
                <div class="xai-stat">
                    <div class="xai-stat-value">100%</div>
                    <div class="xai-stat-label">Your Data, Exportable</div>
                </div>
            </div>
        </div>
    </section>


    <!-- ── SYSTEM PREVIEW (Video Demo) ── -->
    <section class="xai-section-sm" id="preview">
        <div class="xai-container">
            <div class="xai-preview xai-reveal">
                <div class="xai-preview-frame">
                    <!-- Browser chrome bar -->
                    <div class="xai-preview-bar">
                        <div class="xai-preview-dot"></div>
                        <div class="xai-preview-dot"></div>
                        <div class="xai-preview-dot"></div>
                        <span class="xai-preview-title">Wangari Farm OS - System Overview</span>
                    </div>
                    <!-- Video demo -->
                    <video
                        class="xai-preview-video"
                        style="width:100%; display:block; border-radius:0 0 12px 12px; object-fit:cover;"
                        autoplay
                        loop
                        muted
                        playsinline
                        preload="metadata"
                    >
                        <source src="/Frontend/images/wvideo.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <!-- Caption below frame -->
                <p class="xai-preview-caption">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4V8z"/></svg>
                    See Wangari in action - the real farm management system
                </p>
            </div>
        </div>
    </section>

    <!-- ── IMPACT STATS ── -->

    <section class="xai-impact-band">
        <div class="xai-container">
            <div class="xai-impact-grid">
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">30</div>
                    <div class="xai-impact-label">Trial days with full web access</div>
                </div>
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">7</div>
                    <div class="xai-impact-label">Integrated hubs in one system</div>
                </div>
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">All</div>
                    <div class="xai-impact-label">Web modules included in trial</div>
                </div>
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">1</div>
                    <div class="xai-impact-label">Workspace for your farm records</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── WHAT IS WANGARI ── -->
    <section class="xai-section" id="features">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">What Wangari Does</div>
                <h2>One platform built<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">for African farms</span></h2>
                <p style="max-width:640px;margin:0 auto;">Wangari is a complete farm management system designed for poultry farmers, livestock keepers, crop growers, feed millers, and agri-vets. Replace notebooks and spreadsheets with live dashboards your whole team can use, on any device, anywhere.</p>
            </div>

            <!-- 3-column explainer -->
            <div class="xai-trio xai-reveal">
                <div class="xai-trio-card">
                    <div class="xai-trio-icon" style="background:linear-gradient(135deg,#22c55e22,#16a34a11);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <h3>Runs on your farm</h3>
                    <p>Record daily production, track flock health, manage feed batches and capture sales - all from a phone or tablet in the shed, not just the office.</p>
                </div>
                <div class="xai-trio-card">
                    <div class="xai-trio-icon" style="background:linear-gradient(135deg,#3b82f622,#1d4ed811);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3>Real profit visibility</h3>
                    <p>Know your exact cost per bag, cost per bird, revenue per customer and net profit per month - automatically, with no manual calculations.</p>
                </div>
                <div class="xai-trio-card">
                    <div class="xai-trio-icon" style="background:linear-gradient(135deg,#a855f722,#7c3aed11);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#A855F7" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3>AI that knows your farm</h3>
                    <p>Ask "What was my profit last month?" or "Which batch had the worst FCR?" in plain language and get instant answers pulled from your own data.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── 7 MODULES ── -->
    <section class="xai-section xai-modules-section">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">7 Integrated Hubs</div>
                <h2>Every corner of your farm,<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">connected</span></h2>
                <p>Each hub handles a specific area of farm life. Together they share one database so nothing gets lost between departments.</p>
            </div>

            <div class="xai-modules-grid">

                <!-- Poultry -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#22C55E;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <span class="xai-module-tag">Poultry Hub</span>
                    </div>
                    <h3>Poultry & Flocks</h3>
                    <p>Manage multiple flocks - broilers, layers, chicks. Record daily mortality, egg production, body weight, FCR and feed consumption. Set vaccination schedules and get automatic health alerts.</p>
                    <ul class="xai-module-list">
                        <li>Batch creation & lifecycle tracking</li>
                        <li>Daily production entries (eggs, weight, mortality)</li>
                        <li>Feed-to-production ratio (FCR) auto-calculation</li>
                        <li>Vaccination & medication schedules</li>
                        <li>Flock performance reports</li>
                    </ul>
                </div>

                <!-- Livestock -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#F59E0B;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-3a2 2 0 0 0-2 2v.5M4 7h3a2 2 0 0 1 2 2v.5M12 4v3M9.5 9.5C8 11 7 13 7 15c0 2.8 2.2 5 5 5s5-2.2 5-5c0-2-1-4-2.5-5.5"/></svg>
                        </div>
                        <span class="xai-module-tag">Livestock Hub</span>
                    </div>
                    <h3>Livestock & Dairy</h3>
                    <p>Individual animal records for cattle, goats, sheep and pigs. Track milk production, breeding cycles, weights, treatments and insurance. Know your herd composition at a glance.</p>
                    <ul class="xai-module-list">
                        <li>Individual animal profiles with ear-tag IDs</li>
                        <li>Daily milk yield & lactation tracking</li>
                        <li>Breeding, pregnancy & calving records</li>
                        <li>Treatment history & vet visit logs</li>
                        <li>Herd value & insurance records</li>
                    </ul>
                </div>

                <!-- Crops -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#10B981;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V12M12 12C12 7 7 3 2 3c0 5 4 9 10 9z"/><path d="M12 12c0-5 5-9 10-9-1 5-5 9-10 9"/></svg>
                        </div>
                        <span class="xai-module-tag">Crops Hub</span>
                    </div>
                    <h3>Crops & Fields</h3>
                    <p>Map your fields, plan planting seasons, track input costs (seeds, fertilizer, pesticides) and record harvest yields. Know which field is most profitable per acre.</p>
                    <ul class="xai-module-list">
                        <li>Field mapping with acreage & GPS</li>
                        <li>Planting, irrigation & harvest calendars</li>
                        <li>Input cost tracking per crop & season</li>
                        <li>Yield recording & quality grading</li>
                        <li>Cost-per-kg and profit-per-acre reports</li>
                    </ul>
                </div>

                <!-- Feed -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#EF4444;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/></svg>
                        </div>
                        <span class="xai-module-tag">Feed Hub</span>
                    </div>
                    <h3>Feed Mill & Inventory</h3>
                    <p>Manage raw material stock, create feed formulas, run batch productions and track every bag from mill to flock. Automatic low-stock alerts stop you running out mid-batch.</p>
                    <ul class="xai-module-list">
                        <li>Raw material purchase & stock receipts</li>
                        <li>Feed formula builder (per 100kg batch)</li>
                        <li>Batch production with ingredient deduction</li>
                        <li>Cost-per-bag auto-calculation</li>
                        <li>Low-stock alerts & reorder points</li>
                    </ul>
                </div>

                <!-- Finance -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#6366F1;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <span class="xai-module-tag">Finance Hub</span>
                    </div>
                    <h3>Finance & Cashbook</h3>
                    <p>A full double-entry cashbook, income & expense categories, bank reconciliation, and monthly P&L reports. Works with M-Pesa. Know your exact cash position every day.</p>
                    <ul class="xai-module-list">
                        <li>Income & expense transaction ledger</li>
                        <li>Monthly profit & loss report</li>
                        <li>Bank & M-Pesa reconciliation</li>
                        <li>Budget vs actual tracking</li>
                        <li>Exportable reports (PDF & Excel)</li>
                    </ul>
                </div>

                <!-- Sales & CRM -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#EC4899;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <span class="xai-module-tag">CRM Hub</span>
                    </div>
                    <h3>Sales & Customer CRM</h3>
                    <p>Create orders, issue LPOs and invoices, and track which customers owe you money. Full credit management with statements so you never chase debt blind.</p>
                    <ul class="xai-module-list">
                        <li>Customer profiles & purchase history</li>
                        <li>Order management with product lines</li>
                        <li>LPO & invoice generation (printable)</li>
                        <li>Credit balance & aging report</li>
                        <li>Automated payment reminders</li>
                    </ul>
                </div>

                <!-- Reports -->
                <div class="xai-module-card xai-reveal" style="--mod-color:#0EA5E9;">
                    <div class="xai-module-header">
                        <div class="xai-module-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        </div>
                        <span class="xai-module-tag">Reports Hub</span>
                    </div>
                    <h3>Reports & Analytics</h3>
                    <p>A live dashboard with KPI cards, trend charts and exportable reports covering every hub. Set daily targets, track actuals and get AI-generated weekly summaries.</p>
                    <ul class="xai-module-list">
                        <li>Live KPI dashboard (revenue, costs, stock)</li>
                        <li>Production trend charts</li>
                        <li>Per-hub export (CSV, PDF)</li>
                        <li>Scheduled weekly email summaries</li>
                        <li>AI-powered insights & recommendations</li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    <!-- ── WHY WANGARI ── -->
    <section class="xai-section" style="background: linear-gradient(180deg, #0D3320 0%, #0a2a19 100%); padding: 100px 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(34,197,94,0.3), transparent);"></div>
        <div style="position: absolute; bottom: -100px; right: -100px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(34,197,94,0.08) 0%, transparent 70%); pointer-events: none;"></div>
        <div class="xai-container" style="position: relative; z-index: 1;">
            <div class="why-wangari-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;">
                <!-- Left: Story -->
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); border-radius: 100px; padding: 6px 16px; margin-bottom: 24px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 1 0-16 0c0 4.5 4 8 8 12z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span style="color: #22C55E; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">Our Inspiration</span>
                    </div>
                    <h2 style="color: #fff; font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 24px; font-family: 'Outfit', sans-serif;">
                        Why we named it<br><span style="color: #22C55E; font-family: var(--font-serif); font-style: italic;">Wangari</span>
                    </h2>
                    <p style="color: rgba(255,255,255,0.7); font-size: 1.05rem; line-height: 1.8; margin-bottom: 20px;">
                        Wangari is named after <strong style="color: #fff;">Prof. Wangari Maathai</strong> (1940–2011) — Kenya’s Nobel Peace Prize laureate, founder of the <strong style="color: #22C55E;">Green Belt Movement</strong>, and the first African woman to win the Nobel Prize.
                    </p>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.95rem; line-height: 1.8; margin-bottom: 20px;">
                        She planted over <strong style="color: #fff;">51 million trees</strong> across Kenya, empowering rural women to reclaim their land, fight deforestation, and build sustainable livelihoods. She believed that a healthy environment starts with the people who work the land.
                    </p>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.95rem; line-height: 1.8; margin-bottom: 32px;">
                        We carry her vision forward. Wangari gives farmers the tools to manage their land sustainably — tracking every animal, every crop, every shilling — so they can feed their families and grow their businesses with confidence.
                    </p>
                    <div style="display: flex; gap: 32px;">
                        <div>
                            <div style="color: #22C55E; font-size: 2rem; font-weight: 800;">51M+</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Trees Planted</div>
                        </div>
                        <div>
                            <div style="color: #22C55E; font-size: 2rem; font-weight: 800;">2004</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Nobel Peace Prize</div>
                        </div>
                        <div>
                            <div style="color: #22C55E; font-size: 2rem; font-weight: 800;">1977</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Green Belt Founded</div>
                        </div>
                    </div>
                </div>
                <!-- Right: Quote -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 48px; position: relative;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="rgba(34,197,94,0.15)" style="margin-bottom: 20px;"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
                    <blockquote style="color: rgba(255,255,255,0.85); font-size: 1.3rem; line-height: 1.7; font-family: var(--font-serif); font-style: italic; margin-bottom: 24px;">
                        “It’s the little things citizens do. That’s what will make the difference. My little thing is planting trees.”
                    </blockquote>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #22C55E, #16A34A); display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 1 0-16 0c0 4.5 4 8 8 12z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <div style="color: #fff; font-weight: 600; font-size: 0.95rem;">Prof. Wangari Maathai</div>
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">1940 – 2011 · Nobel Peace Prize Laureate</div>
                        </div>
                    </div>
                    <!-- Decorative leaves -->
                    <div style="position: absolute; top: -20px; right: -20px; opacity: 0.1;">
                        <svg width="120" height="120" viewBox="0 0 24 24" fill="#22C55E"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── HOW IT WORKS ── -->
    <section class="xai-section xai-hiw-section">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Getting Started</div>
                <h2>Up and running in<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">3 simple steps</span></h2>
            </div>
            <div class="xai-hiw-grid">
                <div class="xai-hiw-card xai-reveal">
                    <div class="xai-hiw-step">01</div>
                    <div class="xai-hiw-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>Register & Set Up</h3>
                    <p>Create your farm profile in under 5 minutes. Add your farm name, the modules you need, your team members and their access roles. No IT skills required.</p>
                    <div class="xai-hiw-badge">Free to start</div>
                </div>
                <div class="xai-hiw-card xai-reveal">
                    <div class="xai-hiw-step">02</div>
                    <div class="xai-hiw-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h3>Enter Your First Records</h3>
                    <p>Add your current flocks or herds, import your customer list, enter opening stock levels and feed formulas. Our setup wizard guides you step by step.</p>
                    <div class="xai-hiw-badge">Same day</div>
                </div>
                <div class="xai-hiw-card xai-reveal">
                    <div class="xai-hiw-step">03</div>
                    <div class="xai-hiw-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <h3>Run Your Farm Live</h3>
                    <p>Start recording daily data, issuing invoices and checking reports. Your dashboard updates in real time. The AI assistant is ready to answer questions from day one.</p>
                    <div class="xai-hiw-badge">Instant insights</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── VS SPREADSHEETS ── -->
    <section class="xai-section">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Why not just use Excel?</div>
                <h2>Wangari vs.<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">Spreadsheets</span></h2>
            </div>
            <div class="xai-compare-wrap xai-reveal">
                <table class="xai-compare-table">
                    <thead>
                        <tr>
                            <th>Capability</th>
                            <th class="xai-col-wangari">
                                <span class="xai-col-badge">Wangari</span>
                            </th>
                            <th class="xai-col-other">Excel / Notebooks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Works on mobile in the shed</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>Multi-user access with roles</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>Auto-calculates feed costs & FCR</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>Live inventory with low-stock alerts</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>Customer credit & aging reports</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>Printable LPOs & Invoices</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>AI assistant for farm questions</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>Vaccination & reminder alerts</td><td class="yes">✓</td><td class="no">✗</td></tr>
                        <tr><td>One-click P&L reports</td><td class="yes">✓</td><td class="partial">Manual</td></tr>
                        <tr><td>Data backup & security</td><td class="yes">✓ Auto</td><td class="partial">Manual</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ── TESTIMONIALS ── -->
    <section class="xai-section" id="testimonials">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Built Around Your Work</div>
                <h2>See what your team can <span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">manage</span></h2>
                <p>Use the trial with your own records and judge the workflow before choosing a paid plan.</p>
            </div>
            <div class="xai-testimonials">
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <?php for($i=0;$i<5;$i++): ?><svg width="16" height="16" viewBox="0 0 24 24" fill="#F59E0B"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
                    </div>
                    <p>Keep production, feed costs, sales and credit records together instead of switching between notebooks and spreadsheets.</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">N</div>
                        <div>
                            <div class="xai-testimonial-name">Farm operations</div>
                            <div class="xai-testimonial-role">Production and health records</div>
                        </div>
                    </div>
                </div>
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <?php for($i=0;$i<5;$i++): ?><svg width="16" height="16" viewBox="0 0 24 24" fill="#F59E0B"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
                    </div>
                    <p>Track feed formulas, stock movement and production costs so your reports start with the records your team entered.</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">O</div>
                        <div>
                            <div class="xai-testimonial-name">Inventory and production</div>
                            <div class="xai-testimonial-role">Stock, formulas and costing</div>
                        </div>
                    </div>
                </div>
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <?php for($i=0;$i<5;$i++): ?><svg width="16" height="16" viewBox="0 0 24 24" fill="#F59E0B"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
                    </div>
                    <p>Manage customers, orders and credit from the same account, with responsive screens for phones and computers.</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">A</div>
                        <div>
                            <div class="xai-testimonial-name">Sales and customers</div>
                            <div class="xai-testimonial-role">Orders, invoices and credit</div>
                        </div>
                    </div>
                </div>
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <?php for($i=0;$i<5;$i++): ?><svg width="16" height="16" viewBox="0 0 24 24" fill="#F59E0B"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
                    </div>
                    <p>Give owners, managers and record keepers the access they need while keeping permissions and farm data under your control.</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">M</div>
                        <div>
                            <div class="xai-testimonial-name">Teams and permissions</div>
                            <div class="xai-testimonial-role">Role-based access</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- ── MOBILE APP COMING SOON ── -->
    <section class="xai-section xai-app-section" id="mobile-app">
        <div class="xai-container">
            <div class="xai-app-wrap xai-reveal">
                <div class="xai-app-content">
                    <div class="xai-app-tag">Coming Q4 2026</div>
                    <h2>Use Wangari on the device<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">your farm already has</span></h2>
                    <p>The responsive web system works on phones, tablets and computers. A Windows desktop installer is available for accounts that have been activated by the administrator.</p>
                    
                    <div class="xai-app-features">
                        <div class="xai-app-feat-item">
                            <div class="xai-app-feat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><path d="M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10"/></svg>
                            </div>
                            <div>
                                <h4>100% Offline Mode</h4>
                                <p>Record milk yields, mortality rates, and feed usage with zero internet. Syncs when you hit Wi-Fi.</p>
                            </div>
                        </div>
                        <div class="xai-app-feat-item">
                            <div class="xai-app-feat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            </div>
                            <div>
                                <h4>Push Notifications</h4>
                                <p>Immediate vaccination alerts, critical inventory level alerts, and customer payment due reminders.</p>
                            </div>
                        </div>
                        <div class="xai-app-feat-item">
                            <div class="xai-app-feat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            </div>
                            <div>
                                <h4>Ear-Tag Camera Scanner</h4>
                                <p>Scan cow ear-tags or raw feed barcodes directly using your phone's native camera to load records instantly.</p>
                            </div>
                        </div>
                    </div>

                    <div class="xai-app-badges">
                        <div class="xai-badge-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 4.17c.66-.81 1.11-1.93.99-3.06-1 .04-2.22.67-2.94 1.51-.64.74-1.2 1.88-1.05 2.99 1.12.09 2.26-.58 3-1.44"/></svg>
                            <div>
                                <span>Download on the</span>
                                <strong>App Store</strong>
                            </div>
                            <span class="xai-badge-soon">Soon</span>
                        </div>
                        <div class="xai-badge-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5.27v13.46c0 .76.43 1.45 1.11 1.79l9.3-9.3L4.11 3.48C3.43 3.82 3 4.51 3 5.27m15.42 6.73-3.66-3.66-1.07 1.07 4.73 4.73c.46-.23.78-.71.78-1.26a1.4 1.4 0 0 0-.78-1.26M10.14 2.11 4.75.05A1.4 1.4 0 0 0 3 1.31v1.17l7.14 7.14M16.5 8.3l-5.29-5.29-1.07 1.07 6.36 6.36c.46-.23.78-.71.78-1.26 0-.55-.32-1.03-.78-1.26"/></svg>
                            <div>
                                <span>Get it on</span>
                                <strong>Google Play</strong>
                            </div>
                            <span class="xai-badge-soon">Soon</span>
                        </div>
                    </div>
                </div>
                
                <!-- Phone Frame Visual -->
                <div class="xai-app-visual">
                    <div class="xai-phone-mockup">
                        <div class="xai-phone-screen">
                            <div class="xai-phone-notch"></div>
                            
                            <!-- Inside Phone View (Mini Dashboard card) -->
                            <div class="xai-mini-app">
                                <div class="xai-mini-header">
                                    <span>Wangari App</span>
                                    <div class="xai-mini-wifi">Offline Mode Enabled</div>
                                </div>
                                
                                <div class="xai-mini-kpi">
                                    <span>Today's Milk Yield</span>
                                    <h2>142.5 Liters</h2>
                                    <div class="xai-mini-pill">+8.2% from yesterday</div>
                                </div>

                                <div class="xai-mini-alert">
                                    <div class="xai-mini-alert-icon">!</div>
                                    <div>
                                        <strong>Vaccination Reminder</strong>
                                        <p>Batch B4 Layers: Newcastle vaccine due in 2 days.</p>
                                    </div>
                                </div>

                                <div class="xai-mini-list">
                                    <div class="xai-mini-item">
                                        <span>Mortality (Daily)</span>
                                        <strong>2 / 500</strong>
                                    </div>
                                    <div class="xai-mini-item">
                                        <span>Feed Used (Bags)</span>
                                        <strong>3.5 Bags</strong>
                                    </div>
                                    <div class="xai-mini-item">
                                        <span>Egg Collection</span>
                                        <strong>12 Crates</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── CTA ── -->
    <section class="xai-section-sm">

        <div class="xai-container">
            <div class="xai-cta xai-reveal">
                <h2>Built with <span style="font-family:var(--font-serif);font-style:italic;">iMeanTech</span></h2>
                <p>iMeanTech designs and supports Wangari's digital farm management platform for practical, connected operations.</p>
                <div class="xai-cta-actions">
                    <a href="https://imeantech.com" target="_blank" rel="noopener noreferrer" class="xai-btn xai-btn-primary xai-btn-lg">
                        Visit iMeanTech.com
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ── FAQ ── -->
    <section class="xai-section" id="faq">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">FAQ</div>
                <h2>Frequently Asked <span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">Questions</span></h2>
            </div>
            <div class="xai-faq xai-reveal">
                <?php
                $faqs = [
                    ["What exactly is Wangari?", "Wangari is an all-in-one farm management system - a mix of ERP, CRM and operations software. It handles production, inventory, feed, sales, credit, and finance for farms and agribusinesses of every size."],
                    ["Which types of farms does it support?", "Wangari supports poultry farms (broilers & layers), dairy & livestock farms, crop farms, feed mills, agro-vets and mixed farming operations. Each hub can be turned on or off based on what your farm does."],
                    ["Do I need internet to use Wangari?", "Wangari is a web app that requires internet to sync data. However, you can install it on a local server on your farm so it works on your LAN without an internet connection."],
                    ["Does it work on my phone?", "Yes. Wangari is fully responsive and works on any smartphone, tablet or computer. Capture production in the shed and check reports from home - no app download needed."],
                    ["How many users can I add?", "There is no hard limit on users. You can add as many team members as you need, each with their own role (Admin, Manager, Worker, Viewer) controlling what they can see and do."],
                    ["Is my data safe and who owns it?", "You own your data, always. Records are encrypted, access is role-based, and you can export or delete everything at any time. No lock-in, no hidden fees."],
                    ["Can I export my data?", "Yes. Every report, ledger and record can be exported as CSV or PDF. You can also run database backups at any time."],
                    ["Does it integrate with M-Pesa?", "The finance hub lets you record M-Pesa payments and reconcile them with your cashbook. Direct payment API checkout is not being advertised until the live integration is configured and tested."],
                    ["How do I get started?", "Click Get Started Free above, create your farm profile, add your first flock or herd and begin recording. Our setup wizard and support team walk you through everything."],
                ];
                foreach ($faqs as $i => $faq):
                ?>
                <div class="xai-faq-item <?php echo $i === 0 ? 'open' : ''; ?>">
                    <div class="xai-faq-question" onclick="this.parentElement.classList.toggle('open')">
                        <span><?php echo htmlspecialchars($faq[0]); ?></span>
                        <svg class="xai-faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div class="xai-faq-answer">
                        <p><?php echo htmlspecialchars($faq[1]); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="xai-footer">
    <div class="xai-container">
        <div class="xai-footer-inner">
            <!-- Brand & Description -->
            <div>
                <div class="xai-footer-brand">
                    <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                    Wangari<span>.</span>
                </div>
                <p class="xai-footer-desc">Smart Farming for a Sustainable Future. All-in-one farm management system for poultry, livestock, crops, and finances - built for African agriculture.</p>
                <div class="xai-footer-contact">
                    <a href="mailto:info@imeantech.com" class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                        info@imeantech.com
                    </a>
                    <a href="tel:+254114971070" class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/></svg>
                        +254 114 971 070
                    </a>
                    <div class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                        Waris Mall, Ruiru, Kenya
                    </div>
                </div>
            </div>
            
            <!-- Product Links -->
            <div>
                <h4>Product</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/about.php">About</a></li>
                    <li><a href="/Frontend/pages/services.php">Services</a></li>
                    <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
                    <li><a href="/Frontend/pages/contact.php">Contact</a></li>
                </ul>
            </div>
            
            <!-- Company Links -->
            <div>
                <h4>Company</h4>
                <ul class="xai-footer-links">
                    <li><a href="https://imeantech.com" target="_blank">iMeanTech</a></li>
                    <li><a href="/Frontend/pages/about.php">About</a></li>
                    <li><a href="/Frontend/pages/contact.php">Contact</a></li>
                    <li><a href="/Frontend/pages/faq.php">FAQ</a></li>
                </ul>
            </div>
            
            <!-- Legal Links -->
            <div>
                <h4>Legal</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/privacy.php">Privacy Policy</a></li>
                    <li><a href="/Frontend/pages/terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="xai-footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</span>
            <div class="xai-footer-credits">
                Built by <a href="https://imeantech.com" target="_blank">iMeanTech</a>
            </div>
        </div>
    </div>
</footer>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // ===== NAVIGATION SCROLL EFFECT =====
    const nav = document.getElementById('mainNav');
    const handleNavScroll = () => {
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', handleNavScroll, { passive: true });
    handleNavScroll(); // Initial check
    
    // ===== MOBILE MENU =====
    const mobileMenu   = document.getElementById('mobileMenu');
    const mobileMenuBtn   = document.getElementById('mobileMenuBtn');
    const mobileMenuClose = document.getElementById('mobileMenuClose');

    function openMenu() {
        mobileMenu.classList.add('open');
        document.body.style.overflow = 'hidden';
        mobileMenuClose?.focus();
    }

    function closeMenu() {
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
        mobileMenuBtn?.focus();
    }

    mobileMenuBtn?.addEventListener('click', openMenu);
    mobileMenuClose?.addEventListener('click', closeMenu);

    // Close when any link inside the menu is clicked
    mobileMenu?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });

    // Close on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && mobileMenu?.classList.contains('open')) closeMenu();
    });

    // Close when clicking the dark backdrop area (not the drawer itself)
    mobileMenu?.addEventListener('click', e => {
        if (e.target === mobileMenu) closeMenu();
    });


    // ===== SCROLL REVEAL ANIMATION =====

    const revealElements = document.querySelectorAll('.xai-reveal');
    const revealOnScroll = () => {
        revealElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight - 100) {
                el.classList.add('visible');
            }
        });
    };
    
    window.addEventListener('scroll', revealOnScroll, { passive: true });
    revealOnScroll(); // Initial check
    
    // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // ===== ACTIVE NAV LINK HIGHLIGHT =====
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.xai-nav-links a');
    
    const highlightNavLink = () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (window.scrollY >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    };
    
    window.addEventListener('scroll', highlightNavLink, { passive: true });
});
</script>

<style>
    @media (max-width: 768px) {
        .why-wangari-grid {
            grid-template-columns: 1fr !important;
            gap: 40px !important;
        }
    }
</style>
</body>
</html>
