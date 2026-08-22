<?php
/** Wangari Desktop App download page. */
declare(strict_types=1);

$page_title = 'Download Wangari - Smart Farm Manager';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$userOS = 'windows';
if (stripos($ua, 'Mac') !== false) $userOS = 'mac';
elseif (stripos($ua, 'Linux') !== false) $userOS = 'linux';

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
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --green-400:#4ADE80; --green-500:#22C55E; --green-600:#16A34A; --slate-400:#94A3B8; --slate-500:#64748B; --slate-900:#0F172A; }
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter Tight',sans-serif; background:var(--slate-900); color:#fff; overflow-x:hidden; }
        .dl-topbar { position:relative; z-index:2; max-width:1200px; margin:0 auto; padding:20px 24px 0; display:flex; align-items:center; justify-content:space-between; }
        .dl-brand { display:inline-flex; align-items:center; gap:10px; color:#fff; font-size:1.2rem; font-weight:800; letter-spacing:-.03em; }
        .dl-brand img { width:34px; height:34px; border-radius:8px; object-fit:contain; }
        .dl-brand span { color:var(--green-400); }
        .dl-home { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border:1px solid rgba(255,255,255,.16); border-radius:999px; color:#fff; font-size:14px; font-weight:700; transition:background .2s ease,transform .2s ease; }
        .dl-home:hover { background:rgba(255,255,255,.1); transform:translateY(-1px); }
        .dl-home svg { width:16px; height:16px; }
        .dl-hero { position:relative; padding:74px 24px 80px; text-align:center; overflow:hidden; }
        .dl-hero::before { content:''; position:absolute; top:-200px; left:50%; transform:translateX(-50%); width:800px; height:800px; background:radial-gradient(circle,rgba(34,197,94,.15) 0%,transparent 60%); pointer-events:none; }
        .dl-hero > * { position:relative; z-index:1; }
        .dl-hero-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 16px; border-radius:999px; background:rgba(74,222,128,.1); border:1px solid rgba(74,222,128,.2); color:var(--green-400); font-size:13px; font-weight:600; margin-bottom:24px; }
        .dl-hero-badge .dot { width:6px; height:6px; border-radius:50%; background:var(--green-400); }
        .dl-hero h1 { font-size:clamp(2.5rem,5vw,4rem); font-weight:800; line-height:1.1; letter-spacing:-1.5px; margin:0 auto 20px; max-width:700px; }
        .dl-hero h1 span { background:linear-gradient(135deg,var(--green-400),#86EFAC); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .dl-hero p { font-size:1.15rem; color:var(--slate-400); max-width:560px; margin:0 auto 40px; line-height:1.6; }
        .dl-buttons { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin-bottom:24px; }
        .dl-btn { display:inline-flex; align-items:center; gap:12px; padding:18px 32px; border-radius:16px; font-size:16px; font-weight:700; text-decoration:none; transition:all .3s ease; cursor:pointer; border:none; }
        .dl-btn svg { width:22px; height:22px; }
        .dl-btn-primary { background:linear-gradient(135deg,var(--green-600),var(--green-500)); color:#fff; box-shadow:0 4px 24px rgba(22,163,74,.3); }
        .dl-btn-primary:hover { transform:translateY(-3px); box-shadow:0 8px 32px rgba(22,163,74,.4); }
        .dl-btn-primary.detected { border:2px solid var(--green-400); }
        .dl-btn-secondary { background:rgba(255,255,255,.06); color:#fff; border:1px solid rgba(255,255,255,.12); }
        .dl-btn-secondary:hover { background:rgba(255,255,255,.1); transform:translateY(-2px); }
        .dl-btn-disabled { opacity:.48; cursor:not-allowed; }
        .dl-btn-disabled:hover { transform:none; }
        .dl-btn small { font-size:12px; opacity:.7; font-weight:400; }
        .dl-subtext { color:var(--slate-500); font-size:13px; margin:8px auto 0; }
        .dl-account-note { display:inline-flex; align-items:center; gap:8px; margin-top:18px; padding:10px 14px; border:1px solid rgba(255,255,255,.1); border-radius:12px; color:var(--slate-400); font-size:13px; }
        .dl-account-note svg { width:16px; height:16px; color:var(--green-400); }
        .dl-features,.dl-reqs,.dl-changelog { margin:0 auto; padding:70px 24px; }
        .dl-features { max-width:1100px; }
        .dl-reqs { max-width:900px; }
        .dl-changelog { max-width:700px; }
        .dl-features h2,.dl-reqs h2,.dl-changelog h2 { text-align:center; font-size:2rem; font-weight:800; margin-bottom:12px; letter-spacing:-.5px; }
        .dl-features .sub { text-align:center; color:var(--slate-400); margin-bottom:48px; }
        .features-grid,.reqs-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; }
        .reqs-grid { grid-template-columns:repeat(3,1fr); }
        .feature-card,.req-card { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:28px; transition:all .3s ease; }
        .feature-card:hover { background:rgba(255,255,255,.06); border-color:rgba(74,222,128,.2); transform:translateY(-2px); }
        .feature-card .icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px; }
        .feature-card .icon svg { width:24px; height:24px; }
        .feature-card h3,.req-card h3 { font-size:16px; font-weight:700; margin-bottom:8px; }
        .feature-card p,.req-card li { font-size:13px; color:var(--slate-400); line-height:1.6; }
        .req-card { padding:24px; text-align:center; }
        .req-card .icon { width:32px; height:32px; margin:0 auto 12px; display:block; color:var(--green-400); }
        .req-card ul { list-style:none; }
        .req-card li { padding:4px 0; }
        .dl-changelog h2 { margin-bottom:40px; }
        .changelog-entry { border-left:2px solid var(--green-600); padding:0 0 32px 24px; position:relative; }
        .changelog-entry::before { content:''; position:absolute; left:-5px; top:2px; width:8px; height:8px; border-radius:50%; background:var(--green-500); }
        .changelog-entry h3 { font-size:16px; margin-bottom:4px; }
        .changelog-entry .date { font-size:12px; color:var(--slate-500); margin-bottom:8px; }
        .changelog-entry ul { padding-left:18px; }
        .changelog-entry li { font-size:13px; color:var(--slate-400); line-height:1.8; }
        @media (max-width:768px) { .dl-topbar { padding-top:14px; } .dl-brand { font-size:1rem; } .dl-home { padding:8px 12px; } .reqs-grid { grid-template-columns:1fr; } .dl-buttons { flex-direction:column; align-items:center; } .dl-btn { width:100%; max-width:320px; justify-content:center; } }
    </style>
</head>
<body>
<header class="dl-topbar" aria-label="Wangari navigation">
    <a class="dl-brand" href="/" aria-label="Wangari home"><img src="/Frontend/images/wangari-logo.png" alt="">Wangari<span>.</span></a>
    <a class="dl-home" href="/"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>Back Home</a>
</header>
<main>
    <section class="dl-hero">
        <div class="dl-hero-badge"><span class="dot"></span>Version <?php echo $appVersion; ?> - <?php echo $releaseDate; ?></div>
        <h1>Manage your farm, <span>offline or online</span></h1>
        <p>Download Wangari Desktop for Windows, Mac, or Linux. Work offline and sync your data when you reconnect.</p>
        <div class="dl-buttons">
            <a href="/Frontend/downloads/download.php?platform=windows" class="dl-btn dl-btn-primary <?php echo $userOS === 'windows' ? 'detected' : ''; ?>" id="btn-win" onclick="trackDownload('windows')"><i data-lucide="monitor" aria-hidden="true"></i>Download for Windows <small>.exe</small></a>
            <a href="#platform-status" class="dl-btn dl-btn-secondary dl-btn-disabled" id="btn-mac" aria-disabled="true" onclick="return platformUnavailable(event, 'macOS')"><i data-lucide="apple" aria-hidden="true"></i>macOS build pending <small>.dmg</small></a>
            <a href="#platform-status" class="dl-btn dl-btn-secondary dl-btn-disabled" id="btn-linux" aria-disabled="true" onclick="return platformUnavailable(event, 'Linux')"><i data-lucide="terminal" aria-hidden="true"></i>Linux build pending <small>.AppImage</small></a>
        </div>
        <p class="dl-subtext" id="platform-status">Windows installer available now. macOS and Linux builds will be published separately. v<?php echo $appVersion; ?> - <?php echo $releaseDate; ?></p>
        <div class="dl-account-note"><i data-lucide="lock-keyhole" aria-hidden="true"></i>Create an account or sign in, then enter the license code issued by the administrator to use the desktop app.</div>
    </section>
    <section class="dl-features">
        <h2>Everything you need, <em style="font-family:'Instrument Serif',serif;font-style:italic;color:var(--green-400)">offline or online</em></h2><p class="sub">The desktop app includes everything in the web version, plus offline access and local data sync.</p>
        <div class="features-grid">
            <article class="feature-card"><div class="icon" style="background:rgba(74,222,128,.1);color:var(--green-400)"><i data-lucide="wifi-off"></i></div><h3>Full Offline Mode</h3><p>Use the entire app without internet. Data is stored locally in an encrypted SQLite database.</p></article>
            <article class="feature-card"><div class="icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i data-lucide="refresh-cw"></i></div><h3>Auto Background Sync</h3><p>When you reconnect, changes sync automatically to the cloud with no extra work.</p></article>
            <article class="feature-card"><div class="icon" style="background:rgba(245,158,11,.1);color:#FBBF24"><i data-lucide="activity"></i></div><h3>Real-Time Dashboard</h3><p>Track animals, inventory, orders, revenue, and farm health with live KPIs.</p></article>
            <article class="feature-card"><div class="icon" style="background:rgba(139,92,246,.1);color:#A78BFA"><i data-lucide="users"></i></div><h3>Team Management</h3><p>Invite workers with Farm Codes and control what each role can see.</p></article>
            <article class="feature-card"><div class="icon" style="background:rgba(236,72,153,.1);color:#F472B6"><i data-lucide="bird"></i></div><h3>Livestock Tracking</h3><p>Track tags, health records, vaccinations, weight monitoring, and mortality.</p></article>
            <article class="feature-card"><div class="icon" style="background:rgba(34,197,94,.1);color:var(--green-400)"><i data-lucide="wallet-cards"></i></div><h3>Financial Management</h3><p>Track income, expenses, invoices, LPOs, reports, and payment records.</p></article>
        </div>
    </section>
    <section class="dl-reqs"><h2>System Requirements</h2><div class="reqs-grid">
        <article class="req-card"><i class="icon" data-lucide="monitor"></i><h3>Windows</h3><ul><li><?php echo $minWindows; ?></li><li><?php echo $minRam; ?></li><li><?php echo $minDisk; ?></li><li>PHP 8.1+ bundled</li></ul></article>
        <article class="req-card"><i class="icon" data-lucide="apple"></i><h3>macOS</h3><ul><li><?php echo $minMac; ?></li><li><?php echo $minRam; ?></li><li><?php echo $minDisk; ?></li><li>PHP 8.1+ bundled</li></ul></article>
        <article class="req-card"><i class="icon" data-lucide="terminal"></i><h3>Linux</h3><ul><li><?php echo $minLinux; ?></li><li><?php echo $minRam; ?></li><li><?php echo $minDisk; ?></li><li>PHP 7.4+ bundled</li></ul></article>
    </div></section>
    <section class="dl-changelog"><h2>What's New in v<?php echo $appVersion; ?></h2>
        <div class="changelog-entry"><h3>v<?php echo $appVersion; ?> - <?php echo $releaseDate; ?></h3><div class="date">Latest release</div><ul><li>Full offline mode with SQLite local database</li><li>Automatic background sync</li><li>Farm Code system for worker invitations</li><li>Role-based access levels</li><li>Super Admin Control Center</li><li>License protection and offline grace handling</li></ul></div>
        <div class="changelog-entry"><h3>v1.0.0 - June 2026</h3><div class="date">Initial release</div><ul><li>Web dashboard with login, registration, and admin panel</li><li>Animal tracking, inventory, orders, and finances</li><li>Google OAuth sign-in</li><li>Electron desktop app shell</li></ul></div>
    </section>
    <section style="text-align:center;padding:40px 24px 100px"><h2 style="font-size:1.8rem;font-weight:800;margin-bottom:16px">Ready to get started?</h2><p style="color:var(--slate-400);margin-bottom:32px;font-size:1rem">Create your account before activating the desktop app.</p><div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap"><a href="/Frontend/pages/register.php" class="dl-btn dl-btn-primary">Create Free Account <i data-lucide="arrow-right"></i></a><a href="/Frontend/pages/login.php" class="dl-btn dl-btn-secondary">Sign In</a></div></section>
</main>
<footer class="xai-footer"><div class="xai-container"><div class="xai-footer-inner"><div><div class="xai-footer-brand"><img src="/Frontend/images/wangari-logo.png" alt="Wangari">Wangari<span>.</span></div><p class="xai-footer-desc">Smart farming tools for poultry, livestock, crops, and finances.</p><div class="xai-footer-contact"><a href="mailto:info@imeantech.com" class="xai-footer-contact-item"><i data-lucide="mail"></i>info@imeantech.com</a></div></div><div><h4>Product</h4><ul class="xai-footer-links"><li><a href="/Frontend/pages/download.php">Download App</a></li><li><a href="/Frontend/pages/pricing.php">Pricing</a></li></ul></div><div><h4>Legal</h4><ul class="xai-footer-links"><li><a href="/Frontend/pages/privacy.php">Privacy</a></li><li><a href="/Frontend/pages/terms.php">Terms</a></li></ul></div></div><div class="xai-footer-bottom"><span>&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</span><div class="xai-footer-credits">Built by <a href="https://imeantech.com" target="_blank" rel="noopener">iMeanTech</a></div></div></div></footer>
<script>
function trackDownload(os) { console.log('[wangari] Download initiated:', os); }
function platformUnavailable(event, platform) { event.preventDefault(); document.getElementById('platform-status').textContent = platform + ' build is not published yet. Please use the Windows installer or check back after the next release.'; return false; }
(function() { const ua = navigator.userAgent; let detected = 'windows'; if (ua.includes('Mac')) detected = 'mac'; else if (ua.includes('Linux')) detected = 'linux'; const button = document.getElementById('btn-' + detected); if (button && !button.classList.contains('dl-btn-disabled')) { button.classList.add('detected'); button.parentNode.insertBefore(button, button.parentNode.firstChild); } })();
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
