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
    <link rel="stylesheet" href="/Frontend/assets/vendor/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-sections.css">

    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <link rel="stylesheet" href="/Frontend/assets/css/mobile-fix.css">
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
            <h1>Last Month, Your Farm<br>Made <span class="xai-blank">KES _______</span> or<br>Lost <span class="xai-blank">KES _______</span>.<br><span>Wangari Tells You in 30 Seconds.</span></h1>
            <p class="xai-hero-sub">Stop guessing your profit. Stop losing feed to theft. Stop relying on notebooks that get wet. Wangari tracks every egg, every bag, every shilling. So you know exactly where your money goes.</p>
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
                    <div class="xai-stat-value">74%</div>
                    <div class="xai-stat-label">of Kenyan farms lose money from poor records</div>
                </div>
                <div class="xai-stat">
                    <div class="xai-stat-value">KES 180K</div>
                    <div class="xai-stat-label">lost to feed theft (undetected for months)</div>
                </div>
                <div class="xai-stat">
                    <div class="xai-stat-value">30 sec</div>
                    <div class="xai-stat-label">to know your real profit with Wangari</div>
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
                    <div class="xai-impact-label">Day free trial, no credit card</div>
                </div>
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">WhatsApp</div>
                    <div class="xai-impact-label">Enter data by text, no app needed</div>
                </div>
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">AI</div>
                    <div class="xai-impact-label">Ask questions in Swahili or English</div>
                </div>
                <div class="xai-impact-item xai-reveal">
                    <div class="xai-impact-num">Offline</div>
                    <div class="xai-impact-label">Works without internet, syncs later</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── WHAT IS WANGARI ── -->
    <section class="xai-section" id="features">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">The Problems We Solve</div>
                <h2>Three things killing<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">your farm profits</span></h2>
                <p style="max-width:640px;margin:0 auto;">74% of Kenyan farms lose money from poor record-keeping. Workers steal feed. Notebooks get wet. You never know your real profit. Wangari fixes all three.</p>
            </div>

            <!-- 3-column explainer -->
            <div class="xai-trio xai-reveal">
                <div class="xai-trio-card">
                    <div class="xai-trio-icon" style="background:linear-gradient(135deg,#22c55e22,#16a34a11);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <h3>Stop guessing your numbers</h3>
                    <p>You suspect feed is going missing. You don't know your real cost per bird. Wangari tracks every bag, every egg, every shilling. So you know exactly where your money goes.</p>
                </div>
                <div class="xai-trio-card">
                    <div class="xai-trio-icon" style="background:linear-gradient(135deg,#3b82f622,#1d4ed811);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3>Know your real profit</h3>
                    <p>Stop doing mental math. Wangari calculates your cost per bird, revenue per customer, and net profit automatically, updated every time you enter data.</p>
                </div>
                <div class="xai-trio-card">
                    <div class="xai-trio-icon" style="background:linear-gradient(135deg,#a855f722,#7c3aed11);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#A855F7" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3>AI that catches problems early</h3>
                    <p>"Why are my layers losing eggs?" "When should I vaccinate?" "Which batch is losing money?" Ask in plain language (even in Swahili) and get answers from YOUR data.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── 7 MODULES ── -->
    <section class="xai-section xai-modules-section">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Everything Connected</div>
                <h2>One system for<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">every part of your farm</span></h2>
                <p>Feed, livestock, crops, sales, finance, all in one place. When you buy feed, it updates your costs. When you sell eggs, it updates your profit. Nothing gets lost.</p>
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
                        She planted over <strong style="color: #fff;">51 million trees</strong> across Kenya, helping rural women reclaim their land, fight deforestation, and build sustainable livelihoods. She believed that a healthy environment starts with the people who work the land.
                    </p>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.95rem; line-height: 1.8; margin-bottom: 32px;">
                        We carry her vision forward. Wangari gives farmers the tools to manage their land sustainably. Tracking every animal, every crop, every shilling, so they can feed their families and grow their businesses with confidence.
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
                <h2>Know your profit<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">in 3 simple steps</span></h2>
            </div>
            <div class="xai-hiw-grid">
                <div class="xai-hiw-card xai-reveal">
                    <div class="xai-hiw-step">01</div>
                    <div class="xai-hiw-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>Text us your numbers</h3>
                    <p>Send a WhatsApp message: "eggs 40, mortality 2, feed 3 bags". That's it. Wangari records it, calculates your costs, and updates your profit report.</p>
                    <div class="xai-hiw-badge">60 seconds</div>
                </div>
                <div class="xai-hiw-card xai-reveal">
                    <div class="xai-hiw-step">02</div>
                    <div class="xai-hiw-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h3>See your real numbers</h3>
                    <p>Wangari shows your cost per bird, revenue per customer, and net profit, calculated automatically. No more guessing. No more spreadsheets.</p>
                    <div class="xai-hiw-badge">Instant</div>
                </div>
                <div class="xai-hiw-card xai-reveal">
                    <div class="xai-hiw-step">03</div>
                    <div class="xai-hiw-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <h3>Catch problems early</h3>
                    <p>Wangari's AI alerts you when mortality spikes, feed is running low, or a customer hasn't paid. You fix problems before they cost you money.</p>
                    <div class="xai-hiw-badge">Peace of mind</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── WHATSAPP BOT SHOWCASE ── -->
    <section class="xai-section" style="background: var(--xai-surface);">
        <div class="xai-container">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; max-width: 1000px; margin: 0 auto;">
                <!-- Left: Phone mockup -->
                <div class="xai-reveal" style="display: flex; justify-content: center;">
                    <div style="width: 300px; background: #1a1a2e; border-radius: 32px; padding: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                        <div style="background: #0D3320; border-radius: 24px; overflow: hidden;">
                            <!-- Phone header -->
                            <div style="background: #166534; padding: 12px 16px; display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #22C55E; display: flex; align-items: center; justify-content: center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 1 0-16 0c0 4.5 4 8 8 12z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div>
                                    <div style="color: #fff; font-size: 0.85rem; font-weight: 600;">Wangari Bot</div>
                                    <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem;">online</div>
                                </div>
                            </div>
                            <!-- Chat messages -->
                            <div style="padding: 16px; display: flex; flex-direction: column; gap: 10px; min-height: 320px;">
                                <!-- Farmer message -->
                                <div style="display: flex; justify-content: flex-end;">
                                    <div style="background: #166534; color: #fff; padding: 10px 14px; border-radius: 14px 14px 4px 14px; font-size: 0.85rem; max-width: 80%;">eggs 40, mortality 2, feed 3 bags</div>
                                </div>
                                <!-- Bot response -->
                                <div style="display: flex; justify-content: flex-start;">
                                    <div style="background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); padding: 10px 14px; border-radius: 14px 14px 14px 4px; font-size: 0.85rem; max-width: 85%; line-height: 1.5;">✅ Recorded!<br><br>🐔 Batch B4 Layers:<br>• Eggs: 40 (crate 6.7)<br>• Mortality: 2 (498/500 left)<br>• Feed: 3 bags (47 left)<br><br>💰 Today's revenue: KES 2,400<br>📊 This week's profit: KES 12,800</div>
                                </div>
                                <!-- Farmer message -->
                                <div style="display: flex; justify-content: flex-end;">
                                    <div style="background: #166534; color: #fff; padding: 10px 14px; border-radius: 14px 14px 4px 14px; font-size: 0.85rem; max-width: 80%;">why low eggs?</div>
                                </div>
                                <!-- Bot AI response -->
                                <div style="display: flex; justify-content: flex-start;">
                                    <div style="background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: rgba(255,255,255,0.9); padding: 10px 14px; border-radius: 14px 14px 14px 4px; font-size: 0.85rem; max-width: 85%; line-height: 1.5;">📊 <strong>AI Analysis:</strong><br>Your production dropped 15% this week.<br><br>Possible causes:<br>1) Feed quality: check FCR<br>2) Disease: check mortality<br>3) Heat stress: check weather<br><br>💡 Recommendation: Check feed stock for moisture.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Text -->
                <div class="xai-reveal">
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); border-radius: 100px; padding: 6px 16px; margin-bottom: 20px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span style="color: #22C55E; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">WhatsApp Bot</span>
                    </div>
                    <h2 style="color: var(--xai-text); font-size: 2rem; font-weight: 800; line-height: 1.2; margin-bottom: 16px;">
                        Text your numbers.<br>We do the math.
                    </h2>
                    <p style="color: var(--xai-text-secondary); font-size: 1rem; line-height: 1.7; margin-bottom: 24px;">
                        No app to download. No forms to fill. Just send a WhatsApp message like you already do every day. Wangari records it, calculates your costs, and sends you a profit report, all automatically.
                    </p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 14px;">
                        <li style="color: var(--xai-text); font-size: 0.95rem; display: flex; align-items: flex-start; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg> Send "eggs 40, mortality 2, feed 3 bags". Done.</li>
                        <li style="color: var(--xai-text); font-size: 0.95rem; display: flex; align-items: flex-start; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg> Ask "why low eggs?" and the AI analyzes your data</li>
                        <li style="color: var(--xai-text); font-size: 0.95rem; display: flex; align-items: flex-start; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg> Get daily profit summary at 6pm, automatically</li>
                        <li style="color: var(--xai-text); font-size: 0.95rem; display: flex; align-items: flex-start; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg> Works on ANY phone, smartphone or basic</li>
                    </ul>
                    <a href="/Frontend/pages/register.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; background: #22C55E; color: #000; font-weight: 700; border-radius: 10px; text-decoration: none; transition: all 0.2s;">Try the WhatsApp Bot Free <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                </div>
            </div>
        </div>
    </section>

    <!-- ── VS SPREADSHEETS ── -->
    <section class="xai-section">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Why not just use Excel?</div>
                <h2>Wangari vs.<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">Notebooks & Spreadsheets</span></h2>
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

    <!-- ── PRICING ── -->
    <section class="xai-section" id="pricing" style="background: linear-gradient(180deg, #0D3320 0%, #0a2a19 100%); padding: 100px 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(34,197,94,0.3), transparent);"></div>
        <div class="xai-container" style="position: relative; z-index: 1;">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow" style="color: #22C55E;">Start Free. Upgrade When You See Results.</div>
                <h2 style="color: #fff;">Choose the plan that<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">fits your farm</span></h2>
                <p style="color: rgba(255,255,255,0.6); max-width: 640px; margin: 0 auto;">Every plan includes the WhatsApp bot, mobile access, and daily profit reports. Start free for 30 days, no credit card required.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; max-width: 1000px; margin: 48px auto 0;">
                <!-- Starter -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 36px; position: relative;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Starter</div>
                    <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
                        <span style="color: #fff; font-size: 2.5rem; font-weight: 800;">KES 500</span>
                        <span style="color: rgba(255,255,255,0.4); font-size: 0.9rem;">/month</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 24px; line-height: 1.6;">Perfect for small farms just getting started with digital records.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 12px;">
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> 1 hub of your choice</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> WhatsApp bot for data entry</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Daily profit summary</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Basic reports</li>
                    </ul>
                    <a href="/Frontend/pages/register.php" style="display: block; text-align: center; padding: 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); color: #fff; font-weight: 600; text-decoration: none; transition: all 0.2s;">Start Free Trial</a>
                </div>

                <!-- Pro (Featured) -->
                <div style="background: rgba(34,197,94,0.08); border: 2px solid rgba(34,197,94,0.4); border-radius: 16px; padding: 36px; position: relative; transform: scale(1.03);">
                    <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #22C55E; color: #000; font-size: 0.7rem; font-weight: 800; padding: 4px 16px; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.05em;">Most Popular</div>
                    <div style="color: #22C55E; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Pro</div>
                    <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
                        <span style="color: #fff; font-size: 2.5rem; font-weight: 800;">KES 1,500</span>
                        <span style="color: rgba(255,255,255,0.4); font-size: 0.9rem;">/month</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 24px; line-height: 1.6;">For serious farmers who want real profit visibility across their operation.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 12px;">
                        <li style="color: rgba(255,255,255,0.8); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <strong>3 hubs</strong> of your choice</li>
                        <li style="color: rgba(255,255,255,0.8); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> WhatsApp bot + Push alerts</li>
                        <li style="color: rgba(255,255,255,0.8); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <strong>AI assistant</strong> (ask questions in plain language)</li>
                        <li style="color: rgba(255,255,255,0.8); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Advanced reports + PDF export</li>
                        <li style="color: rgba(255,255,255,0.8); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Vaccination & low-stock reminders</li>
                    </ul>
                    <a href="/Frontend/pages/register.php" style="display: block; text-align: center; padding: 14px; border-radius: 10px; background: #22C55E; color: #000; font-weight: 700; text-decoration: none; transition: all 0.2s;">Start Free Trial →</a>
                </div>

                <!-- Enterprise -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 36px; position: relative;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Enterprise</div>
                    <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
                        <span style="color: #fff; font-size: 2.5rem; font-weight: 800;">KES 3,000</span>
                        <span style="color: rgba(255,255,255,0.4); font-size: 0.9rem;">/month</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 24px; line-height: 1.6;">For large farms, agro-vets, and cooperatives who need everything.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 12px;">
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <strong>All 7 hubs</strong> unlocked</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Full AI + priority support</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Unlimited team members + roles</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Cooperative dashboard</li>
                        <li style="color: rgba(255,255,255,0.7); font-size: 0.9rem; display: flex; align-items: center; gap: 10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> API access</li>
                    </ul>
                    <a href="/Frontend/pages/register.php" style="display: block; text-align: center; padding: 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); color: #fff; font-weight: 600; text-decoration: none; transition: all 0.2s;">Contact Us</a>
                </div>
            </div>

            <!-- Founder's Offer -->
            <div style="text-align: center; margin-top: 40px; padding: 20px; background: rgba(34,197,94,0.06); border: 1px dashed rgba(34,197,94,0.3); border-radius: 12px; max-width: 600px; margin-left: auto; margin-right: auto;">
                <p style="color: #22C55E; font-size: 0.95rem; font-weight: 600; margin: 0 0 4px;">🎯 Founding Farmers Offer</p>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0;">First 1,000 farmers get <strong style="color: #fff;">50% OFF forever</strong>. Limited spots. Claim yours before November.</p>
            </div>
        </div>
    </section>

    <!-- SOCIAL PROOF -->
    <section class="xai-section" id="proof">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Why Farmers Switch</div>
                <h2>The problems Wangari<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">actually solves</span></h2>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; max-width: 900px; margin: 48px auto 0;">
                <!-- Problem 1 -->
                <div style="background: var(--xai-card-bg); border: 1px solid var(--xai-border); border-radius: 16px; padding: 36px; position: relative;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239,68,68,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    </div>
                    <h3 style="color: var(--xai-text); font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">The feed theft problem</h3>
                    <p style="color: var(--xai-text-secondary); font-size: 0.95rem; line-height: 1.7; margin-bottom: 16px;">A poultry farmer in Kiambu had no inventory system. Feed disappeared for months before he noticed. KES 180,000 gone. Wangari tracks every bag from store to shed.</p>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #EF4444;"></div>
                        <span style="color: var(--xai-text-secondary); font-size: 0.85rem;">74% of Kenyan farms lose money from poor records</span>
                    </div>
                </div>

                <!-- Problem 2 -->
                <div style="background: var(--xai-card-bg); border: 1px solid var(--xai-border); border-radius: 16px; padding: 36px; position: relative;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3 style="color: var(--xai-text); font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">The profit guessing game</h3>
                    <p style="color: var(--xai-text-secondary); font-size: 0.95rem; line-height: 1.7; margin-bottom: 16px;">Most farmers cannot tell you their cost per bird or per litre of milk. They guess. Then they run out of cash and wonder why. Wangari calculates it automatically.</p>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #F59E0B;"></div>
                        <span style="color: var(--xai-text-secondary); font-size: 0.85rem;">You will know your exact profit every day</span>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 40px;">
                <a href="/Frontend/pages/register.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: #22C55E; color: #000; font-weight: 700; border-radius: 10px; text-decoration: none; transition: all 0.2s;">Start Your Free Trial <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
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
            <!-- Swiper Slideshow -->
            <div class="xai-slideshow-wrap xai-reveal" style="position:relative;padding-bottom:60px;">
                <div class="swiper xai-slideshow" style="overflow:hidden;">
                    <div class="swiper-wrapper">
                        <!-- Slide 1: Farm Operations -->
                        <div class="swiper-slide">
                            <div style="background:var(--xai-card-bg);border:1px solid var(--xai-border);border-radius:16px;padding:40px;min-height:320px;display:flex;flex-direction:column;justify-content:space-between;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#22C55E22,#16A34A11);display:flex;align-items:center;justify-content:center;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem;font-weight:700;color:var(--xai-lime);text-transform:uppercase;letter-spacing:0.08em;">Farm Operations</div>
                                            <div style="font-size:1.1rem;font-weight:700;color:var(--xai-text);">Daily Records & Health</div>
                                        </div>
                                    </div>
                                    <p style="color:var(--xai-text-secondary);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Record egg counts, mortality, weight gain and vaccinations in one place. No more scattered notebooks. Every entry connects to your batch and updates your profit report automatically.</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Egg Tracking</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Mortality Log</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Vaccinations</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Weight Records</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Slide 2: Inventory -->
                        <div class="swiper-slide">
                            <div style="background:var(--xai-card-bg);border:1px solid var(--xai-border);border-radius:16px;padding:40px;min-height:320px;display:flex;flex-direction:column;justify-content:space-between;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#F59E0B22,#D9770611);display:flex;align-items:center;justify-content:center;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem;font-weight:700;color:#F59E0B;text-transform:uppercase;letter-spacing:0.08em;">Inventory & Store</div>
                                            <div style="font-size:1.1rem;font-weight:700;color:var(--xai-text);">Feed, Stock & Costs</div>
                                        </div>
                                    </div>
                                    <p style="color:var(--xai-text-secondary);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Know exactly how much feed, medicine and supplies you have left. Track every bag of layers mash and every bottle of vaccine. The system alerts you before you run out and shows what each batch is costing you.</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Feed Stock</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Low Alerts</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Cost Tracking</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Auto Reorder</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Slide 3: Sales & Credit -->
                        <div class="swiper-slide">
                            <div style="background:var(--xai-card-bg);border:1px solid var(--xai-border);border-radius:16px;padding:40px;min-height:320px;display:flex;flex-direction:column;justify-content:space-between;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#10B98122,#05966911);display:flex;align-items:center;justify-content:center;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem;font-weight:700;color:#10B981;text-transform:uppercase;letter-spacing:0.08em;">Sales & Finance</div>
                                            <div style="font-size:1.1rem;font-weight:700;color:var(--xai-text);">Orders, Credit & M-Pesa</div>
                                        </div>
                                    </div>
                                    <p style="color:var(--xai-text-secondary);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Track who bought eggs today, who still owes you money, and how much you made this week. Every sale, whether cash, M-Pesa or credit, shows up in your profit report so you always know where your money is.</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">M-Pesa Sales</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Credit Tracking</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Customer Accounts</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Profit Reports</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Slide 4: Teams -->
                        <div class="swiper-slide">
                            <div style="background:var(--xai-card-bg);border:1px solid var(--xai-border);border-radius:16px;padding:40px;min-height:320px;display:flex;flex-direction:column;justify-content:space-between;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#6366F122,#4F46E511);display:flex;align-items:center;justify-content:center;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:0.08em;">Teams & Workers</div>
                                            <div style="font-size:1.1rem;font-weight:700;color:var(--xai-text);">Roles & Access Control</div>
                                        </div>
                                    </div>
                                    <p style="color:var(--xai-text-secondary);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Your farm worker records daily tasks, your manager handles sales, and you see everything. Each person gets the right access. Nobody sees what they shouldn't, and you stay in control of your data.</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Worker Access</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Manager View</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Owner Dashboard</span>
                                        <span style="background:var(--xai-surface);color:var(--xai-text-secondary);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Secure Data</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Navigation arrows -->
                <div class="xai-slideshow-prev" style="position:absolute;left:-16px;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:var(--xai-card-bg);border:1px solid var(--xai-border);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;transition:all 0.2s;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--xai-text)" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </div>
                <div class="xai-slideshow-next" style="position:absolute;right:-16px;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:var(--xai-card-bg);border:1px solid var(--xai-border);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;transition:all 0.2s;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--xai-text)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
                <!-- Pagination dots -->
                <div class="xai-slideshow-pagination" style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);display:flex;gap:8px;"></div>
            </div>
        </div>
    </section>



    <!-- MOBILE APP -->
    <section class="xai-section xai-app-section" id="mobile-app">
        <div class="xai-container">
            <div class="xai-app-wrap xai-reveal">
                <div class="xai-app-content">
                    <div class="xai-app-tag">WhatsApp + Web + Offline</div>
                    <h2>Works where your farm is<br><span style="color:var(--xai-lime);font-family:var(--font-serif);font-style:italic;">not just where the internet is</span></h2>
                    <p>Enter data via WhatsApp (works on any phone), the web app (works on phones, tablets, computers), or offline mode (syncs when you get signal). No smartphone? USSD works too.</p>
                    
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

                    <a href="/Frontend/pages/register.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; background: #22C55E; color: #000; font-weight: 700; border-radius: 10px; text-decoration: none; transition: all 0.2s; margin-top: 8px;">Try Wangari Free <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
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
                    ["What exactly is Wangari?", "Wangari is a farm management system. It tracks your production, inventory, feed, sales, credit, and finances in one place. You can use it from your phone, tablet, or computer."],
                    ["Which types of farms does it support?", "Wangari supports poultry farms (broilers & layers), dairy & livestock farms, crop farms, feed mills, agro-vets and mixed farming operations. Each hub can be turned on or off based on what your farm does."],
                    ["Do I need internet to use Wangari?", "You can use WhatsApp to enter data, which works on any phone. The web app works best with internet, but offline mode lets you record data without a connection and syncs later."],
                    ["Does it work on my phone?", "Yes. Wangari is fully responsive and works on any smartphone, tablet or computer. Capture production in the shed and check reports from home - no app download needed."],
                    ["How many users can I add?", "There is no hard limit on users. You can add as many team members as you need, each with their own role (Admin, Manager, Worker, Viewer) controlling what they can see and do."],
                    ["Is my data safe and who owns it?", "You own your data, always. Records are encrypted, access is role-based, and you can export or delete everything at any time. No lock-in, no hidden fees."],
                    ["Can I export my data?", "Yes. Every report, ledger and record can be exported as CSV or PDF. You can also run database backups at any time."],
                    ["Does it integrate with M-Pesa?", "You can record M-Pesa payments and track them in your cashbook. Direct M-Pesa checkout is coming soon."],
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
                <p class="xai-footer-desc">Farm management system for poultry, livestock, crops, and finances. Built for African agriculture by iMeanTech.</p>
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

<script src="/Frontend/assets/vendor/swiper/swiper-bundle.min.js"></script>

<style>
    @media (max-width: 768px) {
        .why-wangari-grid {
            grid-template-columns: 1fr !important;
            gap: 40px !important;
        }
        .xai-slideshow-prev, .xai-slideshow-next {
            display: none !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var slideshow = new Swiper('.xai-slideshow', {
        slidesPerView: 1,
        spaceBetween: 24,
        loop: true,
        autoplay: { delay: 5000, disableOnInteraction: false },
        pagination: { el: '.xai-slideshow-pagination', clickable: true },
        navigation: { prevEl: '.xai-slideshow-prev', nextEl: '.xai-slideshow-next' },
        breakpoints: {
            640: { slidesPerView: 2 },
            1024: { slidesPerView: 3 }
        }
    });
    // Style pagination dots
    document.querySelectorAll('.xai-slideshow-pagination .swiper-pagination-bullet').forEach(function(dot) {
        dot.style.width = '8px';
        dot.style.height = '8px';
        dot.style.background = 'var(--xai-border)';
        dot.style.opacity = '1';
        dot.style.transition = 'all 0.3s';
    });
    document.querySelectorAll('.xai-slideshow-pagination .swiper-pagination-bullet-active').forEach(function(dot) {
        dot.style.background = 'var(--xai-lime)';
        dot.style.width = '24px';
        dot.style.borderRadius = '4px';
    });
    slideshow.on('slideChange', function() {
        document.querySelectorAll('.xai-slideshow-pagination .swiper-pagination-bullet').forEach(function(dot) {
            dot.style.width = '8px';
            dot.style.borderRadius = '50%';
            dot.style.background = 'var(--xai-border)';
        });
        var active = document.querySelector('.xai-slideshow-pagination .swiper-pagination-bullet-active');
        if (active) {
            active.style.background = 'var(--xai-lime)';
            active.style.width = '24px';
            active.style.borderRadius = '4px';
        }
    });
});
</script>

</body>
</html>
