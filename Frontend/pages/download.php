<?php
/**
 * Wangari Desktop App Download Page
 * Detects OS and shows appropriate download button.
 * Shows version history, system requirements, and feature list.
 */
declare(strict_types=1);
$page_title = 'Download Wangari — Smart Farm Manager';

// Detect user OS from User-Agent
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$userOS = 'windows'; // default
if (stripos($ua, 'Mac') !== false) $userOS = 'mac';
elseif (stripos($ua, 'Linux') !== false) $userOS = 'linux';

// App info
$appVersion = '1.1.0';
$releaseDate = 'August 2026';
$minWindows = 'Windows 10 (64-bit)';
$minMac = 'macOS 12 Monterey';
$minLinux = 'Ubuntu 20.04 / Debian 11+';
$minRam = '4 GB RAM';
$minDisk = '500 MB free space';
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
        :root {
            --green-50: #F0FDF4; --green-100: #DCFCE7; --green-200: #BBF7D0;
            --green-400: #4ADE80; --green-500: #22C55E; --green-600: #16A34A;
            --green-700: #15803D; --green-800: #166534; --green-900: #14532D;
            --slate-50: #F8FAFC; --slate-100: #F1F5F9; --slate-200: #E2E8F0;
            --slate-400: #94A3B8; --slate-500: #64748B; --slate-600: #475569;
            --slate-700: #334155; --slate-800: #1E293B; --slate-900: #0F172A;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter Tight', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--slate-900);
            color: #fff;
            overflow-x: hidden;
        }

        /* ── HERO ── */
        .dl-hero {
            position: relative;
            padding: 100px 24px 80px;
            text-align: center;
            overflow: hidden;
        }
        .dl-hero::before {
            content: '';
            position: absolute;
            top: -200px; left: 50%; transform: translateX(-50%);
            width: 800px; height: 800px;
            background: radial-gradient(circle, rgba(34,197,94,0.15) 0%, transparent 60%);
            pointer-events: none;
        }
        .dl-hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 16px; border-radius: 999px;
            background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.2);
            color: var(--green-400); font-size: 13px; font-weight: 600;
            margin-bottom: 24px;
        }
        .dl-hero-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green-400); }
        .dl-hero h1 {
            font-family: 'Inter Tight', sans-serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
            max-width: 700px;
            margin-left: auto; margin-right: auto;
        }
        .dl-hero h1 span {
            background: linear-gradient(135deg, var(--green-400), #86EFAC);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .dl-hero p {
            font-size: 1.15rem;
            color: var(--slate-400);
            max-width: 560px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }

        /* ── DOWNLOAD BUTTONS ── */
        .dl-buttons {
            display: flex; gap: 16px;
            justify-content: center; flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .dl-btn {
            display: inline-flex; align-items: center; gap: 12px;
            padding: 18px 32px;
            border-radius: 16px;
            font-size: 16px; font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            cursor: pointer; border: none;
        }
        .dl-btn svg { width: 22px; height: 22px; }
        .dl-btn-primary {
            background: linear-gradient(135deg, var(--green-600), var(--green-500));
            color: #fff;
            box-shadow: 0 4px 24px rgba(22,163,74,0.3);
        }
        .dl-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 32px rgba(22,163,74,0.4); }
        .dl-btn-primary.detected { border: 2px solid var(--green-400); }
        .dl-btn-secondary {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .dl-btn-secondary:hover { background: rgba(255,255,255,0.1); transform: translateY(-2px); }

        .dl-subtext {
            color: var(--slate-500);
            font-size: 13px;
            margin-top: 8px;
        }

        /* ── FEATURES GRID ── */
        .dl-features {
            max-width: 1100px;
            margin: 0 auto;
            padding: 80px 24px;
        }
        .dl-features h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .dl-features .sub {
            text-align: center;
            color: var(--slate-400);
            margin-bottom: 48px;
            font-size: 1rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .feature-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 28px;
            transition: all 0.3s ease;
        }
        .feature-card:hover { background: rgba(255,255,255,0.06); border-color: rgba(74,222,128,0.2); transform: translateY(-2px); }
        .feature-card .icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 16px;
        }
        .feature-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .feature-card p { font-size: 13px; color: var(--slate-400); line-height: 1.6; }

        /* ── SYSTEM REQUIREMENTS ── */
        .dl-reqs {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 24px 80px;
        }
        .dl-reqs h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 40px;
        }
        .reqs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .req-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
        }
        .req-card .icon { font-size: 36px; margin-bottom: 12px; display: block; }
        .req-card h3 { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
        .req-card ul { list-style: none; padding: 0; }
        .req-card li {
            font-size: 13px; color: var(--slate-400);
            padding: 4px 0;
        }

        /* ── WHAT'S NEW ── */
        .dl-changelog {
            max-width: 700px;
            margin: 0 auto;
            padding: 60px 24px 80px;
        }
        .dl-changelog h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 40px;
        }
        .changelog-entry {
            border-left: 2px solid var(--green-600);
            padding: 0 0 32px 24px;
            position: relative;
        }
        .changelog-entry::before {
            content: '';
            position: absolute; left: -5px; top: 2px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--green-500);
        }
        .changelog-entry h3 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .changelog-entry .date { font-size: 12px; color: var(--slate-500); margin-bottom: 8px; }
        .changelog-entry ul { padding-left: 18px; }
        .changelog-entry li { font-size: 13px; color: var(--slate-400); line-height: 1.8; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .reqs-grid { grid-template-columns: 1fr; }
            .dl-buttons { flex-direction: column; align-items: center; }
            .dl-btn { width: 100%; max-width: 320px; justify-content: center; }
        }
    </style>
</head>
<body>

<!-- ═══════════ HERO ═══════════ -->
<div class="dl-hero">
    <div class="dl-hero-badge">
        <span class="dot"></span>
        Version <?php echo $appVersion; ?> — <?php echo $releaseDate; ?>
    </div>
    <h1>Manage your farm, <span>offline or online</span></h1>
    <p>Download Wangari Desktop for Windows, Mac, or Linux. Works fully offline — syncs your data when you reconnect.</p>

    <div class="dl-buttons">
        <!-- Windows (primary if detected) -->
        <a href="/Frontend/downloads/wangari-<?php echo $appVersion; ?>-win-x64.exe"
           class="dl-btn dl-btn-primary <?php echo $userOS === 'windows' ? 'detected' : ''; ?>"
           id="btn-win" onclick="trackDownload('windows')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/></svg>
            Download for Windows
            <span style="font-size:12px;opacity:0.7;font-weight:400">.exe</span>
        </a>

        <!-- Mac -->
        <a href="/Frontend/downloads/wangari-<?php echo $appVersion; ?>-mac.dmg"
           class="dl-btn dl-btn-secondary <?php echo $userOS === 'mac' ? 'detected' : ''; ?>"
           id="btn-mac" onclick="trackDownload('mac')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
            Download for macOS
            <span style="font-size:12px;opacity:0.7;font-weight:400">.dmg</span>
        </a>

        <!-- Linux -->
        <a href="/Frontend/downloads/wangari-<?php echo $appVersion; ?>-linux.AppImage"
           class="dl-btn dl-btn-secondary <?php echo $userOS === 'linux' ? 'detected' : ''; ?>"
           id="btn-linux" onclick="trackDownload('linux')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.504 0c-.155 0-.315.008-.48.021-4.226.333-3.105 4.807-3.17 6.298-.076 1.092-.3 1.953-1.05 3.02-.885 1.051-2.127 2.75-2.716 4.521-.278.832-.41 1.684-.287 2.489a.424.424 0 00-.11.135c-.26.268-.45.6-.663.839-.199.199-.485.267-.797.4-.313.136-.658.269-.864.68-.09.189-.136.394-.132.602 0 .199.027.4.055.536.058.399.116.728.04.97-.249.68-.28 1.145-.106 1.484.174.334.535.47.94.601.81.2 1.91.135 2.774.6.926.466 1.866.67 2.616.47.526-.116.97-.464 1.208-.946.587-.003 1.23-.269 2.26-.334.699-.058 1.574.267 2.577.2.025.134.063.198.114.333l.003.003c.391.778 1.113 1.368 1.884 1.43.084.006.167.01.25.012 1.114-.048 2.24-.833 2.937-1.807.035-.049.067-.1.099-.151a.46.46 0 00.04-.149c.058-.54.025-1.175-.15-2.01-.201-.776-.6-1.744-1.096-2.588-.496-.844-1.076-1.534-1.327-1.776-.199-.199-.485-.4-.835-.67a.8.8 0 01-.119-.1c-.333-.334-.728-.6-1.048-.8a5.8 5.8 0 00-.578-.3c-.248-.1-.451-.167-.632-.334-.148-.135-.246-.334-.252-.535-.002-.1.017-.198.05-.298.2-.5.133-1.003-.133-1.537-.199-.399-.533-.733-.932-.932a3.38 3.38 0 00-.467-.166c-.133-.034-.233-.067-.3-.134a.65.65 0 01-.1-.167c-.06-.133-.1-.334-.133-.535-.067-.4.033-.735.2-1.068.167-.334.334-.5.567-.668.134-.1.234-.167.3-.267.134-.2.134-.467.067-.8-.067-.333-.267-.6-.567-.8-.2-.133-.467-.267-.834-.467-.367-.199-.8-.466-1.267-.732-.466-.267-.9-.534-1.2-.868-.167-.2-.334-.333-.467-.534a3.96 3.96 0 01-.267-.533c-.067-.2-.067-.467.067-.8.134-.334.267-.6.534-.867.267-.267.533-.466.8-.6.134-.067.234-.134.3-.2.134-.133.2-.333.2-.6 0-.267-.067-.467-.2-.667a3.62 3.62 0 00-.534-.466c-.333-.2-.667-.334-1.067-.467-.4-.133-.8-.267-1.134-.4-.133-.067-.267-.134-.4-.2-.133-.067-.267-.134-.333-.2a.57.57 0 01-.134-.2c0-.067-.033-.134-.033-.267 0-.2.033-.4.133-.533.067-.2.2-.4.333-.534.2-.2.4-.333.667-.4.2-.067.467-.134.733-.2.467-.067.934-.2 1.334-.4.467-.267.867-.534 1.134-.868.133-.133.2-.333.2-.533 0-.134-.067-.267-.134-.4-.133-.2-.267-.4-.467-.534-.4-.2-.8-.333-1.2-.4-.4-.067-.8-.133-1.134-.267a4.2 4.2 0 01-.733-.4c-.2-.133-.4-.267-.533-.467-.2-.267-.334-.6-.4-1.001-.067-.4-.067-.8.067-1.2.2-.6.467-1.067.867-1.467.2-.2.467-.333.667-.534.2-.2.333-.4.467-.667.133-.2.2-.467.2-.734 0-.2-.033-.4-.134-.533-.067-.2-.133-.4-.333-.534-.267-.2-.6-.333-.934-.4a4.5 4.5 0 00-.666-.067h-.2z"/></svg>
            Download for Linux
            <span style="font-size:12px;opacity:0.7;font-weight:400">.AppImage</span>
        </a>
    </div>

    <p class="dl-subtext">Also available as .deb and .rpm packages · v<?php echo $appVersion; ?> · <?php echo $releaseDate; ?></p>
</div>

<!-- ═══════════ FEATURES ═══════════ -->
<div class="dl-features">
    <h2>Everything you need, <em style="font-family:'Instrument Serif',serif;font-style:italic;color:var(--green-400)">offline or online</em></h2>
    <p class="sub">The desktop app includes everything in the web version, plus offline access and local data sync.</p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="icon" style="background:rgba(74,222,128,0.1);color:var(--green-400)">📴</div>
            <h3>Full Offline Mode</h3>
            <p>Use the entire app without internet. All data is stored locally in an encrypted SQLite database. No internet? No problem.</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="background:rgba(59,130,246,0.1);color:#60A5FA">🔄</div>
            <h3>Auto Background Sync</h3>
            <p>When you reconnect, changes sync automatically to the cloud. Push every 2 min, pull every 5 min. Zero effort.</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="background:rgba(245,158,11,0.1);color:#FBBF24"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <h3>Real-Time Dashboard</h3>
            <p>Live stats on animals, inventory, orders, revenue. See your farm's health at a glance with animated charts and KPIs.</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="background:rgba(139,92,246,0.1);color:#A78BFA">👥</div>
            <h3>Team Management</h3>
            <p>Invite workers with Farm Codes. Assign roles — manager, stock keeper, sales, vet, field worker. Control what each role can see.</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="background:rgba(236,72,153,0.1);color:#F472B6">🐔</div>
            <h3>Livestock Tracking</h3>
            <p>Track every animal with tag numbers, health records, vaccinations, weight monitoring, and mortality tracking.</p>
        </div>
        <div class="feature-card">
            <div class="icon" style="background:rgba(34,197,94,0.1);color:var(--green-400)">💰</div>
            <h3>Financial Management</h3>
            <p>Track income, expenses, invoices, LPOs. Generate financial reports. M-Pesa integration for payment tracking.</p>
        </div>
    </div>
</div>

<!-- ═══════════ SYSTEM REQUIREMENTS ═══════════ -->
<div class="dl-reqs">
    <h2>System Requirements</h2>
    <div class="reqs-grid">
        <div class="req-card">
            <span class="icon">🪟</span>
            <h3>Windows</h3>
            <ul>
                <li><?php echo $minWindows; ?></li>
                <li><?php echo $minRam; ?></li>
                <li><?php echo $minDisk; ?></li>
                <li>PHP 8.1+ (bundled)</li>
            </ul>
        </div>
        <div class="req-card">
            <span class="icon">🍎</span>
            <h3>macOS</h3>
            <ul>
                <li><?php echo $minMac; ?></li>
                <li><?php echo $minRam; ?></li>
                <li><?php echo $minDisk; ?></li>
                <li>PHP 8.1+ (bundled)</li>
            </ul>
        </div>
        <div class="req-card">
            <span class="icon">🐧</span>
            <h3>Linux</h3>
            <ul>
                <li><?php echo $minLinux; ?></li>
                <li><?php echo $minRam; ?></li>
                <li><?php echo $minDisk; ?></li>
                <li>PHP 7.4+ (bundled)</li>
            </ul>
        </div>
    </div>
</div>

<!-- ═══════════ WHAT'S NEW ═══════════ -->
<div class="dl-changelog">
    <h2>What's New in v<?php echo $appVersion; ?></h2>

    <div class="changelog-entry">
        <h3>v<?php echo $appVersion; ?> — <?php echo $releaseDate; ?></h3>
        <div class="date">Latest release</div>
        <ul>
            <li>✅ Full offline mode with SQLite local database</li>
            <li>✅ Automatic background sync (push/pull)</li>
            <li>✅ Farm Code system — invite workers with a code</li>
            <li>✅ 9 role-based access levels (Owner → Guest)</li>
            <li>✅ Super Admin Control Center with user management</li>
            <li>✅ Real-time sync queue status in the sidebar</li>
            <li>✅ Premium UI with glassmorphism and animations</li>
            <li>✅ 14-day offline grace period</li>
            <li>✅ Hardware fingerprint license protection</li>
            <li>✅ Redis session caching + OPcache performance boost</li>
        </ul>
    </div>

    <div class="changelog-entry">
        <h3>v1.0.0 — June 2026</h3>
        <div class="date">Initial release</div>
        <ul>
            <li>Web dashboard with login, register, admin panel</li>
            <li>Animal tracking, inventory, orders, finances</li>
            <li>Google OAuth sign-in</li>
            <li>Electron desktop app shell</li>
        </ul>
    </div>
</div>

<!-- ═══════════ CTA ═══════════ -->
<div style="text-align:center;padding:40px 24px 100px;">
    <h2 style="font-size:1.8rem;font-weight:800;margin-bottom:16px;">Ready to get started?</h2>
    <p style="color:var(--slate-400);margin-bottom:32px;font-size:1rem;">Create your account and start managing your farm today.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <a href="/Frontend/pages/register.php" class="dl-btn dl-btn-primary">
            Create Free Account
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="/Frontend/pages/login.php" class="dl-btn dl-btn-secondary">
            Sign In
        </a>
    </div>
</div>

<script>
// Track downloads (placeholder for analytics)
function trackDownload(os) {
    console.log('[wangari] Download initiated:', os);
    // Could send to analytics endpoint
}

// Highlight detected OS button
(function() {
    const ua = navigator.userAgent;
    let detected = 'windows';
    if (ua.includes('Mac')) detected = 'mac';
    else if (ua.includes('Linux')) detected = 'linux';

    const btn = document.getElementById('btn-' + detected);
    if (btn) {
        btn.classList.add('detected');
        // Move detected button first
        btn.parentNode.insertBefore(btn, btn.parentNode.firstChild);
    }
})();
</script>

<!-- Footer -->
<footer class="xai-footer">
    <div class="xai-container">
        <div class="xai-footer-inner">
            <div>
                <div class="xai-footer-brand">
                    <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                    Wangari<span>.</span>
                </div>
                <p class="xai-footer-desc">Smart Farming for a Sustainable Future.</p>
                <div class="xai-footer-contact">
                    <a href="mailto:info@imeantech.com" class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                        info@imeantech.com
                    </a>
                </div>
            </div>
            <div>
                <h4>Product</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/download.php">Download App</a></li>
                    <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h4>Legal</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/privacy.php">Privacy</a></li>
                    <li><a href="/Frontend/pages/terms.php">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="xai-footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</span>
            <div class="xai-footer-credits">
                Built by <a href="https://imeantech.com" target="_blank">iMeanTech</a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
