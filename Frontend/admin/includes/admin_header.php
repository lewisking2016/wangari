<?php
/**
 * Admin page header and left navigation.
 */
declare(strict_types=1);

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../includes/config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    require_once dirname(__DIR__, 3) . '/Backend/config/session.php';
    wangariStartSession();
}

if (!isset($page_title)) $page_title = 'Admin Console';
// Admin access check (Basic authentication for ANY admin area)
// Admin access check (Basic authentication for ANY admin area)
if (empty($_SESSION['user_id']) || !wangariIsFarmSystemRole((string)($_SESSION['role'] ?? ''))) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

// Authorization logic for specific roles
$isAdmin = in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager'], true);
$isStockManager = ($_SESSION['role'] ?? '') === 'stock_manager';

// Restrict Stock Manager to ONLY stock-related pages
if ($isStockManager) {
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
    $allowedStockPages = ['stock_dashboard.php', 'stock_calculator.php', 'stock_recipes.php', 'stock_costing.php', 'stock_alerts.php', 'incoming_stock.php', 'profile.php', 'hub_inventory.php', 'hub_finance.php', 'hub_crm.php', 'dashboard.php'];
    if (!in_array($currentPage, $allowedStockPages) && strpos($currentPage, 'stock_') === false && strpos($currentPage, 'incoming_') === false) {
        // Redirect stock managers away from non-stock pages (like orders, settings, users)
        header('Location: /Frontend/admin/stock_dashboard.php');
        exit;
    }
}

$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

/* ── Global CSRF enforcement for admin POST requests ──
 * Every form and same-origin fetch gets a token injected via JS below,
 * so any POST arriving without a valid token is rejected here, before
 * any page handler runs. (login.php and public pages do their own checks.)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!function_exists('verifyCSRFToken') || !verifyCSRFToken($submittedToken)) {
        http_response_code(419);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Security token expired. Please refresh the page and try again.';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        :root {
            /* x.ai-style semantic tokens */
            --admin-primary: #166534;
            --admin-primary-light: #1B7A3D;
            --admin-primary-hover: #145214;
            --admin-accent: #22C55E;
            --admin-accent-dark: #9DBF2E;
            --admin-dark: #0B1220;
            --admin-body-bg: #FAFBFC;
            --admin-border: #E7EAF0;
            --admin-card-bg: #FFFFFF;
            --admin-text-main: #334155;
            --admin-text-heading: #0F172A;
            --admin-text-muted: #64748B;
            --admin-radius: 20px;
            --admin-radius-sm: 12px;
            --admin-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            --admin-shadow-hover: 0 8px 32px rgba(15, 23, 42, 0.08);
        }

        html { 
            overflow-y: auto;
        }
        body.admin-layout { 
            overflow-y: auto;
            background: var(--admin-body-bg); 
            color: var(--admin-text-main);
            font-family: 'Inter Tight', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
        }

        /* Serif accent for big numbers, Growvi style */
        .admin-layout .stat-card strong,
        .admin-layout .kpi-value,
        .admin-layout .serif-num {
            font-family: 'Instrument Serif', serif;
            font-weight: 400;
            letter-spacing: -0.5px;
        }

        nav.navbar { display: none !important; }
        
        .admin-shell { 
            display: block;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .admin-sidebar { 
            width: 280px; 
            background: var(--admin-sidebar-bg); 
            border-right: 1px solid rgba(255,255,255,0.08); 
            padding: 20px 16px; 
            position: sticky; 
            top: 0; 
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(11, 18, 32, 0.06); 
            box-sizing: border-box;
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(34, 197, 94, 0.35) transparent;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(34, 197, 94, 0.35);
        }

        .admin-sidebar-brand p {
            color: #ffffff;
        }

        .admin-sidebar-brand small {
            color: #9CA3AF;
        }

        .admin-sidebar-nav a {
            color: #C7CDD8;
        }

        .admin-sidebar-nav a:hover {
            color: var(--admin-accent);
            background: rgba(255,255,255,0.06);
        }

        .admin-sidebar-nav a.active {
            background: var(--admin-accent);
            color: #0B1220;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.25);
        }

        .admin-sidebar-nav .sidebar-dropdown a {
            color: #9CA3AF;
        }

        .admin-sidebar-nav .sidebar-dropdown a:hover {
            color: var(--admin-accent);
            background: rgba(255,255,255,0.06);
        }

        .admin-sidebar-nav .sidebar-dropdown a.active {
            background: rgba(34, 197, 94, 0.12);
            color: var(--admin-accent);
        }

        .admin-sidebar-nav .sidebar-dropdown {
            border-left: 2px solid rgba(255,255,255,0.12);
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .admin-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(27, 94, 32, 0.2);
            border-radius: 4px;
        }

        .admin-sidebar-brand { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin-bottom: 24px; 
            padding: 0 8px;
        }

        .admin-sidebar-brand p { 
            margin: 0; 
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem; 
            font-weight: 800; 
            color: var(--admin-text-heading);
            letter-spacing: -0.5px;
        }

        .admin-sidebar-brand small { 
            display: block; 
            color: #475569; 
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .admin-sidebar-nav { 
            list-style: none; 
            padding: 0; 
            margin: 0; 
            display: flex;
            flex-direction: column;
            gap: 6px; 
            flex-grow: 1;
        }

        .admin-sidebar-nav a { 
            display: flex; 
            align-items: center;
            gap: 12px;
            padding: 10px 14px; 
            border-radius: 4px; 
            color: #475569; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.95rem;
            border: 1px solid transparent; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
        }

        .admin-sidebar-nav a i {
            width: 20px;
            height: 20px;
            transition: transform 0.2s ease;
        }

        .admin-sidebar-nav a:hover { 
            color: var(--admin-primary);
            background: rgba(27, 94, 32, 0.04);
        }

        .admin-sidebar-nav a.active { 
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-light) 100%); 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.15);
        }

        .admin-sidebar-nav a.active i {
            transform: scale(1.05);
        }

        /* Sidebar Dropdown Styling */
        .admin-sidebar-nav .dropdown-trigger {
            cursor: pointer;
            justify-content: space-between !important;
        }

        .admin-sidebar-nav .sidebar-dropdown {
            list-style: none;
            padding: 0;
            margin: 0;
            display: none;
            flex-direction: column;
            gap: 2px;
            padding-left: 20px;
            margin-top: 4px;
            margin-bottom: 10px;
            border-left: 2px solid var(--admin-border);
            margin-left: 24px;
        }

        .admin-sidebar-nav .sidebar-dropdown.open {
            display: flex;
        }

        .admin-sidebar-nav .dropdown-trigger .chevron {
            width: 16px;
            height: 16px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .admin-sidebar-nav .dropdown-trigger.open .chevron {
            transform: rotate(180deg);
        }

        .admin-sidebar-nav .sidebar-dropdown a {
            font-size: 0.88rem;
            padding: 8px 14px;
            font-weight: 500;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .admin-sidebar-nav .sidebar-dropdown a i {
            width: 16px;
            height: 16px;
            opacity: 0.7;
            transition: all 0.2s ease;
        }

        .admin-sidebar-nav .sidebar-dropdown a:hover {
            color: var(--admin-primary);
            background: rgba(27, 94, 32, 0.04);
            text-decoration: none;
        }

        .admin-sidebar-nav .sidebar-dropdown a:hover i {
            opacity: 1;
            transform: translateX(2px);
        }

        .admin-sidebar-nav .sidebar-dropdown a.active {
            background: rgba(27, 94, 32, 0.08);
            color: var(--admin-primary);
            font-weight: 700;
            box-shadow: none;
        }

        .admin-sidebar-nav .sidebar-dropdown a.active i {
            opacity: 1;
            color: var(--admin-primary);
        }

        .admin-sidebar-footer { 
            margin-top: auto; 
            padding-top: 14px;
            border-top: 1px solid var(--admin-border);
        }

        .admin-sidebar-footer .btn { 
            width: 100%; 
            justify-content: center; 
            border-radius: 4px;
        }

        /* Content Area */
        .admin-content { 
            padding: 24px; 
            max-width: 1400px;
            margin: 0 auto;
            margin-left: 268px;
            width: calc(100% - 268px);
            box-sizing: border-box;
            min-width: 0;
        }

        /* Top utility bar */
        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 14px 20px;
            box-shadow: var(--admin-shadow);
        }

        .admin-top-bar .welcome-message h2 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-size: 1.2rem;
            color: var(--admin-text-heading);
        }

        .admin-top-bar .welcome-message p {
            margin: 2px 0 0 0;
            font-size: 0.85rem;
            color: #475569;
        }

        .admin-profile-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 4px;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-accent) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
        }

        /* Dashboard Cards & Layout */
        .admin-card { 
            background: var(--admin-card-bg); 
            border: 1px solid var(--admin-border); 
            border-radius: var(--admin-radius); 
            box-shadow: var(--admin-shadow); 
            padding: 24px;
            box-sizing: border-box;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: visible;
            min-width: 0;
            position: relative;
        }

        .admin-card:hover {
            box-shadow: var(--admin-shadow-hover);
            transform: translateY(-4px);
        }

        .dashboard-hero { 
            display: flex; 
            justify-content: space-between; 
            gap: 20px; 
            align-items: flex-start; 
            margin-bottom: 26px; 
        }

        .dashboard-hero .hero-text h1 { 
            margin: 0; 
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem; 
            font-weight: 700;
            color: var(--admin-text-heading);
            letter-spacing: -0.5px;
        }

        .dashboard-hero .hero-text p { 
            color: #64748b; 
            margin-top: 8px; 
            line-height: 1.6; 
        }

        .hero-pill { 
            display: inline-flex; 
            gap: 8px; 
            align-items: center; 
            background: rgba(27, 94, 32, 0.06); 
            color: var(--admin-primary); 
            padding: 8px 16px; 
            border-radius: 4px; 
            font-weight: 600; 
            font-size: 0.85rem;
        }

        .stat-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 16px; 
            margin-bottom: 24px;
        }        .stat-card { 
            padding: 20px; 
            border-radius: var(--admin-radius); 
            background: var(--admin-card-bg); 
            border: 1px solid var(--admin-border); 
            box-shadow: var(--admin-shadow); 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 0;
            overflow: visible;
            position: relative;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--admin-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--admin-shadow-hover);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover .stat-card-icon {
            transform: scale(1.15) rotate(5deg);
            background: rgba(22, 101, 52, 0.15);
        }

        .stat-card:hover strong {
            color: var(--admin-primary);
        }

        .stat-card-info {
            display: flex;
            flex-direction: column;
        }

        .stat-card small { 
            color: #64748b; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
        }

        .stat-card strong { 
            display: block; 
            margin-top: 8px; 
            font-size: 2rem; 
            color: var(--admin-text-heading); 
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(22, 101, 52, 0.08);
            color: var(--admin-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card-icon.accent {
            background: rgba(255, 193, 7, 0.1);
            color: #d97706;
        }

        .stat-card-icon.info {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            min-width: 600px;
        }

        .admin-table th {
            padding: 12px 14px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            border-bottom: 1px solid var(--admin-border);
            background: #F8FAFC;
            white-space: nowrap;
        }

        .admin-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #F0F2F6;
            font-size: 0.85rem;
            color: var(--admin-text-main);
            word-break: break-word;
        }

        .admin-table tr:hover td {
            background: #FAFBFD;
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pill-success {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-pill-warning {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-pill-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Form elements */
        .admin-form-group {
            margin-bottom: 20px;
        }

        .admin-form-label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--admin-text-heading);
            margin-bottom: 6px;
        }

        .admin-form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #D8DEE8;
            border-radius: var(--admin-radius-sm);
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            box-sizing: border-box;
        }

        .admin-form-control:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 4px rgba(22, 101, 52, 0.12);
        }

        .admin-actions { display: flex; flex-wrap: wrap; gap: 12px; }

        /* ═══════════════════════════════════════════════════════════════ */
        /* ADMIN BUTTON SYSTEM, overrides global .btn for admin context   */
        /* ═══════════════════════════════════════════════════════════════ */

        /* Base admin button reset, tighter padding than front-end */
        .admin-layout .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 999px;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: 'Inter Tight', sans-serif;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
            border: 1.5px solid transparent;
            text-decoration: none;
            white-space: nowrap;
            line-height: 1.2;
        }

        .admin-layout .btn i,
        .admin-layout .btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* Small variant used for table row actions */
        .admin-layout .btn-sm {
            padding: 6px 13px;
            font-size: 0.78rem;
            gap: 6px;
            border-radius: 999px;
        }

        .admin-layout .btn-sm i,
        .admin-layout .btn-sm svg {
            width: 14px;
            height: 14px;
        }

        /* Primary, green */
        .admin-layout .btn-primary {
            background: linear-gradient(135deg, #14532D 0%, #1B7A3D 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 14px rgba(22, 101, 52, 0.25);
        }

        .admin-layout .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(22, 101, 52, 0.32);
            color: #ffffff;
        }

        /* Outline, border only */
        .admin-layout .btn-outline {
            border: 1.5px solid #D8DEE8;
            color: #475569;
            background: #fff;
        }

        .admin-layout .btn-outline:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
            background: #F0FDF4;
        }

        /* Trans (transparent ghost), for table row secondary actions */
        .admin-layout .btn-trans {
            background: rgba(241,245,249,0.8);
            border: 1.5px solid #e2e8f0;
            color: #475569;
        }

        .admin-layout .btn-trans:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #1e293b;
            transform: translateY(-1px);
        }

        /* Danger variant */
        .admin-layout .btn-danger {
            background: #fee2e2;
            border: 1.5px solid #fecaca;
            color: #b91c1c;
        }

        .admin-layout .btn-danger:hover {
            background: #fca5a5;
            border-color: #f87171;
            color: #7f1d1d;
            transform: translateY(-1px);
        }

        /* Warning variant */
        .admin-layout .btn-warning {
            background: #fef3c7;
            border: 1.5px solid #fde68a;
            color: #b45309;
        }

        .admin-layout .btn-warning:hover {
            background: #fde68a;
            border-color: #fbbf24;
            color: #78350f;
            transform: translateY(-1px);
        }

        /* Info variant */
        .admin-layout .btn-info {
            background: #dbeafe;
            border: 1.5px solid #bfdbfe;
            color: #1d4ed8;
        }

        .admin-layout .btn-info:hover {
            background: #bfdbfe;
            border-color: #93c5fd;
            color: #1e3a8a;
            transform: translateY(-1px);
        }

        /* ═══════════════════════════════════════════
           Table action button group (View/Edit/Delete)
        ═══════════════════════════════════════════ */
        .tbl-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: flex-end;
        }

        /* Auto-icons for semantic btn-sm links in tables via data attrs */
        .admin-layout a.btn-sm[href*="action=view"],
        .admin-layout a.btn-sm[href*="action=edit"],
        .admin-layout button.btn-sm[onclick*="edit"],
        .admin-layout button.btn-sm[onclick*="delete"] {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
    </style>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- WANGARI ADMIN V2 — complete visual redesign               -->
    <!-- Loads after the legacy block so it wins by source order.  -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <style id="wangari-admin-v2">
        :root {
            --w2-primary: #166534;
            --w2-primary-light: #1B7A3D;
            --w2-lime: #22C55E;
            --w2-ink: #0B1220;
            --w2-cream: #F5F6F8;
            --w2-card: #FFFFFF;
            --w2-border: #E7EAF0;
            --w2-text: #334155;
            --w2-heading: #0F172A;
            --w2-muted: #94A3B8;
            --w2-radius: 14px;
            --w2-radius-sm: 9px;
            --w2-shadow: 0 8px 28px rgba(15, 23, 42, 0.05);
        }

        /* One consistent typeface across the whole admin */
        * {
            font-family: 'Inter Tight', 'Outfit', sans-serif !important;
        }

        body.admin-layout {
            background: var(--w2-cream);
            color: var(--w2-text);
            font-family: 'Inter Tight', sans-serif !important;
        }

        /* ── Cards ── */
        .admin-card {
            background: var(--w2-card) !important;
            border: 1px solid var(--w2-border) !important;
            border-radius: var(--w2-radius) !important;
            box-shadow: var(--w2-shadow) !important;
            padding: 22px !important;
        }

        /* ── Tables ── */
        .table-responsive {
            border-radius: var(--w2-radius-sm);
            border: 1px solid var(--w2-border);
            overflow-x: auto;
        }
        .admin-table th {
            background: #F8FAFC !important;
            color: #64748B !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding: 13px 18px !important;
        }
        .admin-table td {
            padding: 14px 18px !important;
            font-size: 0.9rem !important;
            border-bottom: 1px solid #F0F2F6 !important;
            color: var(--w2-text) !important;
        }
        .admin-table tr:hover td {
            background: #FAFBFD !important;
        }
        .admin-table tbody tr:last-child td {
            border-bottom: none !important;
        }

        /* ── Badges → pills ── */
        .badge-pill {
            border-radius: 999px !important;
            padding: 4px 12px !important;
            font-size: 0.72rem !important;
            font-weight: 600 !important;
        }
        .badge-pill-success { background: #E4F7E9 !important; color: #15803D !important; }
        .badge-pill-warning { background: #FEF5E0 !important; color: #B45309 !important; }
        .badge-pill-danger  { background: #FDE8E8 !important; color: #B91C1C !important; }
        .badge-pill-info    { background: #E0EDFF !important; color: #1D4ED8 !important; }

        /* ── Buttons ── */
        .admin-layout .btn {
            border-radius: 999px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            font-size: 0.88rem !important;
            gap: 8px !important;
        }
        .admin-layout .btn-sm {
            border-radius: 999px !important;
            padding: 6px 13px !important;
            font-size: 0.78rem !important;
        }
        .admin-layout .btn-primary {
            background: linear-gradient(135deg, #14532D 0%, #1B7A3D 100%) !important;
            box-shadow: 0 4px 14px rgba(22, 101, 52, 0.25) !important;
        }
        .admin-layout .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(22, 101, 52, 0.32) !important;
        }
        .admin-layout .btn-outline {
            border: 1.5px solid #D8DEE8 !important;
            color: #475569 !important;
            background: #fff !important;
        }
        .admin-layout .btn-outline:hover {
            border-color: var(--w2-primary) !important;
            color: var(--w2-primary) !important;
            background: #F0FDF4 !important;
        }
        .admin-layout .btn-danger  { border-radius: 999px !important; }
        .admin-layout .btn-warning { border-radius: 999px !important; }
        .admin-layout .btn-info    { border-radius: 999px !important; }
        .admin-layout .btn-success { border-radius: 999px !important; }
        .tbl-actions { gap: 6px !important; }

        /* ── Forms ── */
        .admin-form-control {
            border-radius: var(--w2-radius-sm) !important;
            border: 1.5px solid #D8DEE8 !important;
            padding: 11px 14px !important;
            font-size: 0.92rem !important;
        }
        .admin-form-control:focus {
            border-color: var(--w2-primary) !important;
            box-shadow: 0 0 0 4px rgba(22, 101, 52, 0.12) !important;
        }
        .admin-form-label {
            font-weight: 600 !important;
            font-size: 0.84rem !important;
            color: var(--w2-heading) !important;
        }

        /* ── Stat cards (dashboard KPIs) ── */
        .stat-card {
            border-radius: var(--w2-radius) !important;
            border: 1px solid var(--w2-border) !important;
            box-shadow: var(--w2-shadow) !important;
            padding: 22px !important;
        }
        .stat-card-icon {
            border-radius: 12px !important;
            width: 46px !important;
            height: 46px !important;
        }

        /* ── Page h1s (hub pages use inline styles with Outfit) ── */
        h1, h2, h3, h4, h5 {
            font-family: 'Inter Tight', sans-serif !important;
        }

        /* ── Modals ── */
        div[style*="position:fixed"] {
            border-radius: var(--w2-radius) !important;
        }

        /* ── Topbar ── */
        .admin-top-bar {
            border-radius: var(--w2-radius) !important;
            border: 1px solid var(--w2-border) !important;
            box-shadow: var(--w2-shadow) !important;
            background: var(--w2-card) !important;
            padding: 14px 20px !important;
        }
        .admin-top-bar .welcome-message h2 {
            font-family: 'Inter Tight', sans-serif !important;
            font-size: 1.25rem !important;
            color: var(--w2-heading) !important;
        }

        /* ── Sidebar V2 ── */
        .w2-side {
            width: 268px;
            flex-shrink: 0;
            background: #FFFFFF;
            border-right: 1px solid #E7EAF0;
            color: #334155;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            box-sizing: border-box;
        }
        .w2-side-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 20px 20px 16px;
            border-bottom: 1px solid #E7EAF0;
        }
        .w2-logo {
            height: 42px;
            width: auto;
            border-radius: 10px;
        }
        .w2-brand-name {
            margin: 0;
            font-family: 'Inter Tight', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.3px;
        }
        .w2-brand-sub {
            display: block;
            color: #64748B;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .w2-nav-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 4px 14px 16px;
            scrollbar-width: thin;
            scrollbar-color: #E7EAF0 transparent;
        }
        .w2-nav-section {
            margin: 18px 8px 7px;
            font-size: 0.66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94A3B8;
        }
        .w2-nav-section:first-child { margin-top: 4px; }
        .w2-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
            margin-bottom: 2px;
        }
        .w2-nav-item:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .w2-nav-item.active {
            background: #166534;
            color: #FFFFFF;
        }
        .w2-nav-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .w2-nav-badge {
            margin-left: auto;
            background: #22C55E;
            color: #0B1220;
            font-size: 0.62rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 999px;
            letter-spacing: 0.04em;
        }
        .w2-nav-group { margin-bottom: 2px; }
        .w2-nav-parent {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 10px 12px;
            background: none;
            border: none;
            border-radius: 10px;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: inherit;
            cursor: pointer;
            text-align: left;
            transition: all 0.18s;
        }
        .w2-nav-parent:hover { background: #F1F5F9; color: #0F172A; }
        .w2-nav-parent.open { color: #166534; }
        .w2-nav-chev {
            margin-left: auto;
            width: 15px;
            height: 15px;
            transition: transform 0.25s ease;
        }
        .w2-nav-parent.open .w2-nav-chev { transform: rotate(180deg); }
        .w2-nav-subs {
            padding: 4px 0 6px 26px;
            border-left: 2px solid rgba(34,197,94,0.18);
            margin-left: 21px;
        }
        .w2-nav-sub {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 8px;
            color: #8E97A8;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 500;
            transition: all 0.15s;
        }
        .w2-nav-sub span {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.5;
        }
        .w2-nav-sub:hover { color: #0F172A; background: #F1F5F9; }
        .w2-nav-sub.active { color: var(--w2-lime); font-weight: 700; }
        .w2-nav-sub.active span { opacity: 1; }

        .w2-side-foot {
            padding: 16px 14px;
            border-top: 1px solid #E7EAF0;
            background: #F8FAFC;
            margin-top: auto;
        }
        .w2-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #FFFFFF;
            margin-bottom: 10px;
            border: 1px solid #E7EAF0;
        }
        .w2-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #166534, #1B7A3D);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-family: 'Inter Tight', sans-serif;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .w2-user-meta p { margin: 0; font-size: 0.85rem; font-weight: 700; color: #0F172A; }
        .w2-user-meta span { font-size: 0.7rem; color: #64748B; text-transform: capitalize; }
        .w2-signout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #FEE2E2;
            color: #B91C1C;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.18s;
            border: 1px solid #FECACA;
        }
        .w2-signout:hover { background: #FCA5A5; color: #FFFFFF; }

        /* Hide legacy sidebar if any page still includes it */
        .admin-shell > nav:not(.w2-side) { display: none !important; }

        /* ── Responsive polish ── */
        @media (max-width: 1024px) {
            .admin-content { padding: 14px; margin-left: 268px; width: calc(100% - 268px); }
            .admin-card { padding: 16px; }
            .stat-grid { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
            .stat-card { padding: 16px; }
            .stat-card strong { font-size: 1.5rem; }
        }
        @media (max-width: 640px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 14px; }
            .stat-card strong { font-size: 1.25rem; }
            .stat-card small { font-size: 0.65rem; }
            .stat-card-icon { width: 40px; height: 40px; }
        }
        @media (max-width: 860px) {
            .admin-content { margin-left: 0; width: 100%; }
            .w2-side {
                position: fixed !important;
                top: 0; left: 0; bottom: 0;
                width: 260px !important;
                transform: translateX(-100%);
                transition: transform .22s ease;
                z-index: 1200;
                box-shadow: 0 0 40px rgba(11,18,32,.35);
            }
            body.w2-nav-open .w2-side { transform: translateX(0); }
            .w2-nav-overlay {
                display: none; position: fixed; inset: 0;
                background: rgba(11,18,32,.45); z-index: 1190;
            }
            body.w2-nav-open .w2-nav-overlay { display: block; }
            .w2-mobile-hamburger {
                display: inline-flex !important;
                align-items: center; justify-content: center;
                width: 38px; height: 38px; border-radius: 50%;
                background: #F4F6F9; border: 1px solid var(--w2-border);
                color: var(--w2-primary); cursor: pointer;
            }
            .w2-nav-parent .w2-nav-chev { margin-left: auto; }
            .w2-nav-subs { display: none; }
            .w2-nav-group.open .w2-nav-subs { display: block !important; }
        }
        /* Mobile table reflow: tables become scrollable cards instead of overflowing the page */
        @media (max-width: 700px) {
            .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .admin-table { min-width: 620px; }
            .admin-top-bar { flex-wrap: wrap; gap: 10px; }
            .welcome-message h2 { font-size: 1.15rem; }
            .stat-card { min-width: 140px; }
            .cmd-box { max-width: 100%; margin: 0 8px; }
            .cmd-palette { padding: 8vh 8px 8px; }
        }
        /* Desktop always shows the sidebar; only mobile uses the off-canvas pattern */
        @media (min-width: 861px) { .w2-mobile-hamburger { display: none; } }
        .w2-nav-overlay { display: none; }
    </style>

    <script>
        // Dynamically hide sidebar and topbar elements if loaded inside an iframe
        if (window.self !== window.top) {
            document.documentElement.classList.add('in-iframe');
            const style = document.createElement('style');
            style.textContent = '.admin-shell > nav, .admin-top-bar { display: none !important; } .admin-shell { display: block !important; } .admin-content { padding: 0 !important; margin-left: 0 !important; width: 100% !important; }';
            document.head.appendChild(style);
        }
    </script>
    <?php include __DIR__ . '/help_tooltips.php'; ?>
</head>
<body class="admin-layout">
<script>
    window.WangariAdmin = window.WangariAdmin || {};
    window.WangariAdmin.csrfToken = <?php echo json_encode($csrf_token); ?>;

    // ── Global CSRF injection: every form + same-origin POST fetch carries the token ──
    (function () {
        var TOKEN = window.WangariAdmin.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!TOKEN) return;
        // Hidden input into every POST form (so plain HTML form POSTs pass the global check)
        function injectForms() {
            document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (f) {
                if (f.querySelector('input[name="csrf_token"]')) return;
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = TOKEN;
                f.appendChild(inp);
            });
        }
        // Wrap fetch: add csrf_token to FormData body or JSON body of same-origin POSTs
        var origFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            if ((init.method || '').toUpperCase() === 'POST' && typeof input === 'string' && input.indexOf('http') !== 0) {
                var isJson = (init.headers && String(init.headers['Content-Type'] || init.headers.get && init.headers.get('Content-Type') || '').indexOf('json') !== -1);
                if (isJson && init.body && typeof init.body === 'string') {
                    try {
                        var parsed = JSON.parse(init.body);
                        parsed.csrf_token = TOKEN;
                        init.body = JSON.stringify(parsed);
                    } catch (e) { /* keep original body */ }
                } else if (init.body instanceof FormData) {
                    if (!init.body.has('csrf_token')) init.body.append('csrf_token', TOKEN);
                } else if (init.body && typeof init.body === 'string') {
                    init.body = (init.body ? init.body + '&' : '') + 'csrf_token=' + encodeURIComponent(TOKEN);
                }
                var hdrs = new Headers(init.headers || {});
                hdrs.set('X-CSRF-Token', TOKEN);
                init.headers = hdrs;
            }
            return origFetch.call(this, input, init);
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', injectForms);
        else injectForms();
    })();
</script>
<div class="w2-nav-overlay" id="w2-nav-overlay"></div>
<div class="admin-shell">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>
    <div class="admin-content">
        <!-- Top utility bar (V2) -->
        <div class="admin-top-bar">
            <div class="welcome-message">
                <p style="margin:0 0 2px;color:var(--w2-muted);font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Wangari Admin</p>
                <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
                    Hello, <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['username'] ?? 'Admin'); ?>
                    <span class="badge-pill badge-pill-success" style="font-size:0.7rem;"><?php echo htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? 'super_admin')); ?></span>
                </h2>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button class="w2-mobile-hamburger" id="w2-mobile-hamburger" title="Menu" aria-label="Open menu"><i data-lucide="menu" style="width:20px;height:20px;"></i></button>
                <button id="open-command-palette" title="Quick search & commands (Ctrl+K)" style="background:#F4F6F9;border:1px solid var(--w2-border);cursor:pointer;color:var(--w2-primary);display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;transition:all 0.2s;outline:none;" onmouseover="this.style.background='#E8F5EC'" onmouseout="this.style.background='#F4F6F9'">
                    <i data-lucide="search" style="width:20px;height:20px;"></i>
                </button>
                <button id="open-system-guide" title="System Walkthrough Guide" style="background:#F4F6F9;border:1px solid var(--w2-border);cursor:pointer;color:var(--w2-primary);display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;transition:all 0.2s;outline:none;" onmouseover="this.style.background='#E8F5EC'" onmouseout="this.style.background='#F4F6F9'">
                    <i data-lucide="help-circle" style="width:20px;height:20px;"></i>
                </button>
                <a href="/Frontend/admin/ai_assistant.php" title="Ask Wangari AI" style="background:#F4F6F9;border:1px solid var(--w2-border);color:var(--w2-primary);display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#E8F5EC'" onmouseout="this.style.background='#F4F6F9'">
                    <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
                </a>
                <div class="admin-profile-badge" style="display:flex;align-items:center;gap:10px;background:#F8FAFC;border:1px solid var(--w2-border);border-radius:999px;padding:5px 14px 5px 6px;">
                    <div class="admin-avatar" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#14532D,#22C55E);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-family:'Inter Tight',sans-serif;font-size:0.9rem;">
                        <?php 
                        $initial = strtoupper(substr($_SESSION['first_name'] ?? $_SESSION['username'] ?? 'A', 0, 1));
                        echo $initial;
                        ?>
                    </div>
                    <div style="text-align: left; line-height: 1.2;">
                        <h5 style="margin:0;font-size:0.85rem;font-weight:700;color:var(--w2-heading);"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ COMMAND PALETTE (Ctrl+K) ═══════ -->
        <div id="cmd-palette" class="cmd-palette" role="dialog" aria-modal="true" aria-label="Quick search" style="display:none;">
            <div class="cmd-backdrop" data-cmd-close></div>
            <div class="cmd-box">
                <div class="cmd-input-row">
                    <i data-lucide="search" style="width:18px;height:18px;flex:none;"></i>
                    <input id="cmd-input" type="text" placeholder="Search pages and actions…  (type 'add flock', 'go to sales')" autocomplete="off">
                    <kbd class="cmd-kbd">ESC</kbd>
                </div>
                <div id="cmd-results" class="cmd-results"></div>
                <div class="cmd-footer">
                    <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
                    <span><kbd>Enter</kbd> open</span>
                    <span class="cmd-ai-hint"><i data-lucide="sparkles" style="width:12px;height:12px;"></i> Try "Ask Wangari AI" for farm questions</span>
                </div>
            </div>
        </div>
        <style>
            .cmd-palette{position:fixed;inset:0;z-index:9999;display:flex;align-items:flex-start;justify-content:center;padding:12vh 16px 16px;}
            .cmd-backdrop{position:absolute;inset:0;background:rgba(11,18,32,0.55);backdrop-filter:blur(3px);}
            .cmd-box{position:relative;width:100%;max-width:560px;background:#fff;border-radius:14px;box-shadow:0 24px 60px rgba(11,18,32,0.35),0 0 0 1px rgba(15,23,42,0.06);overflow:hidden;animation:cmdPop .14s ease-out;}
            @keyframes cmdPop{from{transform:translateY(-8px) scale(.98);opacity:0}to{transform:none;opacity:1}}
            .cmd-input-row{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--w2-border,#E7EAF0);color:#64748b;}
            .cmd-input-row input{flex:1;border:none;outline:none;font-size:1rem;font-family:'Inter Tight','Inter',sans-serif;color:var(--w2-heading,#0F172A);background:transparent;}
            .cmd-kbd{border:1px solid var(--w2-border,#E7EAF0);border-radius:5px;padding:2px 6px;font-size:0.7rem;color:#94a3b8;background:#F8FAFC;}
            .cmd-results{max-height:340px;overflow-y:auto;padding:6px;}
            .cmd-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;cursor:pointer;color:var(--w2-text,#334155);font-size:0.92rem;}
            .cmd-item .cmd-ic{width:30px;height:30px;border-radius:7px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:var(--w2-primary,#166534);flex:none;}
            .cmd-item .cmd-path{margin-left:auto;font-size:0.72rem;color:#94a3b8;}
            .cmd-item.active{background:var(--w2-primary,#166534);color:#fff;}
            .cmd-item.active .cmd-ic{background:rgba(255,255,255,0.16);color:#fff;}
            .cmd-item.active .cmd-path{color:rgba(255,255,255,0.75);}
            .cmd-empty{padding:22px;text-align:center;color:#94a3b8;font-size:0.9rem;}
            .cmd-footer{display:flex;gap:14px;align-items:center;padding:10px 16px;border-top:1px solid var(--w2-border,#E7EAF0);font-size:0.72rem;color:#94a3b8;background:#FAFBFC;}
            .cmd-footer kbd,.cmd-footer .cmd-kbd{margin-right:3px;}
            .cmd-ai-hint{margin-left:auto;display:flex;align-items:center;gap:5px;color:var(--w2-primary,#166534);}
        </style>
        <script>
        (function(){
            var ITEMS = [
                {label:'Dashboard', path:'Overview', url:'/Frontend/admin/dashboard.php', icon:'layout-dashboard', keys:['home','overview']},
                {label:'Ask Wangari AI', path:'Overview', url:'/Frontend/admin/ai_assistant.php', icon:'sparkles', keys:['ask','ai','chat','assistant']},
                {label:'Farm Operations — Overview', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=overview', icon:'layout-dashboard', keys:['overview','farm','operations']},
                {label:'Farm Operations — Animals', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=animals', icon:'paw-print', keys:['animal','cow','sheep','goat','pig','tag','livestock']},
                {label:'Farm Operations — Groups (Flocks/Herds)', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=groups', icon:'users', keys:['flock','herd','batch','chicken','group']},
                {label:'Farm Operations — Housing', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=housing', icon:'home', keys:['house','pen','boma','coop','housing']},
                {label:'Farm Operations — Health Records', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=health', icon:'heart-pulse', keys:['health','treatment','vet','sick']},
                {label:'Farm Operations — Vaccinations', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=vaccinations', icon:'syringe', keys:['vaccine','vaccination']},
                {label:'Farm Operations — Daily Production', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=production', icon:'egg', keys:['production','eggs','milk','daily']},
                {label:'Farm Operations — Breeding', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=breeding', icon:'dna', keys:['breed','breeding','sire','dam','pregnancy']},
                {label:'Farm Operations — Feeding', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=feeding', icon:'wheat', keys:['feed','feeding','ration']},
                {label:'Farm Operations — Poultry Tools', path:'Farm Ops', url:'/Frontend/admin/hub_operations.php?tab=poultry', icon:'bird', keys:['broiler','hatchery','egg grading','poultry']},
                {label:'Crops & Fields', path:'Farm Ops', url:'/Frontend/admin/hub_crops.php', icon:'wheat', keys:['crop','field','planting','harvest']},
                {label:'Inventory — Products Catalog', path:'Inventory', url:'/Frontend/admin/hub_inventory.php?tab=products', icon:'package', keys:['product','catalog','sell','price']},
                {label:'Inventory — Farm Equipment', path:'Inventory', url:'/Frontend/admin/hub_inventory.php?tab=equipment', icon:'wrench', keys:['equipment','tool','machine','tractor']},
                {label:'Inventory — Feed & Stock', path:'Inventory', url:'/Frontend/admin/hub_inventory.php?tab=feedstock', icon:'layers', keys:['feed','stock','ingredient','raw']},
                {label:'Inventory — Alerts', path:'Inventory', url:'/Frontend/admin/hub_inventory.php?tab=alerts', icon:'bell', keys:['alert','low stock','reorder']},
                {label:'Sales — Customer Orders', path:'Sales', url:'/Frontend/admin/hub_finance.php?tab=orders', icon:'shopping-cart', keys:['order','customer order','online']},
                {label:'Sales — Sales Register', path:'Sales', url:'/Frontend/admin/hub_finance.php?tab=sales', icon:'receipt', keys:['sales','register','sold']},
                {label:'Sales — Incoming Payments', path:'Sales', url:'/Frontend/admin/hub_finance.php?tab=payments', icon:'banknote', keys:['payment','mpesa','money in']},
                {label:'Sales — Outgoing Expenses', path:'Sales', url:'/Frontend/admin/hub_finance.php?tab=expenses', icon:'arrow-down-circle', keys:['expense','spend','cost','money out']},
                {label:'Sales — Reports & Charts', path:'Sales', url:'/Frontend/admin/hub_finance.php?tab=reports', icon:'bar-chart-3', keys:['report','chart','analytics','profit']},
                {label:'Sales — LPO / Invoices', path:'Sales', url:'/Frontend/admin/lpo.php', icon:'file-text', keys:['lpo','invoice','document']},
                {label:'CRM — Customers', path:'Sales', url:'/Frontend/admin/hub_crm.php?tab=customers', icon:'users', keys:['customer','client','crm']},
                {label:'CRM — Segments', path:'Sales', url:'/Frontend/admin/hub_crm.php?tab=segments', icon:'tags', keys:['segment','group customers']},
                {label:'CRM — Follow-ups', path:'Sales', url:'/Frontend/admin/hub_crm.php?tab=followups', icon:'calendar-check', keys:['follow','reminder customer','credit']},
                {label:'CRM — Contact History', path:'Sales', url:'/Frontend/admin/hub_crm.php?tab=contacts', icon:'history', keys:['contact','note','history']},
                {label:'Credit (Money Owed)', path:'Sales', url:'/Frontend/admin/credit.php', icon:'hand-coins', keys:['credit','debt','owe','aging']},
                {label:'Labour — Workers', path:'People', url:'/Frontend/admin/hub_labour.php?tab=workers', icon:'hard-hat', keys:['worker','staff','labour','employee']},
                {label:'Labour — Attendance', path:'People', url:'/Frontend/admin/hub_labour.php?tab=attendance', icon:'clipboard-check', keys:['attendance','hours']},
                {label:'Labour — Wage Payments', path:'People', url:'/Frontend/admin/hub_labour.php?tab=payments', icon:'wallet', keys:['wage','salary','pay worker']},
                {label:'Team & Messages', path:'People', url:'/Frontend/admin/hub_people.php', icon:'message-square', keys:['message','team','staff account','task']},
                {label:'Reminders & Weather', path:'Tools', url:'/Frontend/admin/hub_reminders.php', icon:'bell', keys:['reminder','weather','alert','due']},
                {label:'Bulk Import/Export', path:'Tools', url:'/Frontend/admin/bulk_import_export.php', icon:'database', keys:['import','export','backup','csv','excel']},
                {label:'Settings', path:'System', url:'/Frontend/admin/hub_settings.php', icon:'settings', keys:['settings','config','app']},
                {label:'Calendar View', path:'System', url:'/Frontend/admin/hub_settings.php?tab=calendar', icon:'calendar', keys:['calendar','schedule']},
                {label:'Roles & Permissions', path:'System', url:'/Frontend/admin/permissions.php', icon:'shield', keys:['role','permission','access','user']},
                {label:'System Logs', path:'System', url:'/Frontend/admin/hub_settings.php?tab=logs', icon:'scroll-text', keys:['log','activity','audit']},
                {label:'Sign Out', path:'System', url:'/Frontend/pages/logout.php', icon:'log-out', keys:['logout','sign out','exit']}
            ];
            var box=document.getElementById('cmd-palette');
            var input=document.getElementById('cmd-input');
            var results=document.getElementById('cmd-results');
            var activeIdx=0, filtered=[];
            function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
            function norm(s){return s.toLowerCase();}
            function render(q){
                q=norm(q||'');
                if(!q){filtered=ITEMS;}
                else{
                    var STOP=['add','create','new','open','go','view','show','to','my','the','record','log','make','see'];
                    var terms=q.split(/\s+/).filter(function(t){return t && STOP.indexOf(t)===-1;});
                    if(!terms.length){filtered=ITEMS;}
                    else{
                        filtered=ITEMS.filter(function(it){
                            var hay=norm(it.label+' '+it.path+' '+(it.keys||[]).join(' '));
                            return terms.every(function(t){return hay.indexOf(t)!==-1;});
                        });
                    }
                }
                activeIdx=0;
                if(!filtered.length){results.innerHTML='<div class="cmd-empty">No matches — try "add flock" or "sales".</div>';return;}
                results.innerHTML=filtered.map(function(it,i){
                    return '<div class="cmd-item'+(i===0?' active':'')+'" data-i="'+i+'"><span class="cmd-ic"><i data-lucide="'+it.icon+'" style="width:16px;height:16px;"></i></span><span>'+esc(it.label)+'</span><span class="cmd-path">'+esc(it.path)+'</span></div>';
                }).join('');
                if(window.lucide)lucide.createIcons({attrs:{'class':'','width':16,'height':16}});
                results.querySelectorAll('.cmd-item').forEach(function(el){
                    el.addEventListener('click',function(){go(filtered[+el.dataset.i]);});
                });
            }
            function go(it){if(!it)return;window.location.href=it.url;}
            function open(){box.style.display='flex';input.value='';render('');input.focus();}
            function close(){box.style.display='none';}
            document.getElementById('open-command-palette').addEventListener('click',open);
            document.addEventListener('keydown',function(e){
                if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();box.style.display==='flex'?close():open();}
                if(box.style.display!=='flex')return;
                if(e.key==='Escape'){close();}
                else if(e.key==='ArrowDown'){e.preventDefault();activeIdx=Math.min(activeIdx+1,filtered.length-1);paint();}
                else if(e.key==='ArrowUp'){e.preventDefault();activeIdx=Math.max(activeIdx-1,0);paint();}
                else if(e.key==='Enter'){e.preventDefault();go(filtered[activeIdx]);}
            });
            function paint(){results.querySelectorAll('.cmd-item').forEach(function(el,i){el.classList.toggle('active',i===activeIdx);el.scrollIntoView({block:'nearest'});});}
            input.addEventListener('input',function(){render(input.value);});
            box.querySelectorAll('[data-cmd-close]').forEach(function(el){el.addEventListener('click',close);});
        })();

        // ── Mobile nav: hamburger toggle ──
        (function(){
            var burger=document.getElementById('w2-mobile-hamburger');
            var overlay=document.getElementById('w2-nav-overlay');
            function setNav(open){
                document.body.classList.toggle('w2-nav-open', open);
            }
            if(burger)burger.addEventListener('click',function(){setNav(!document.body.classList.contains('w2-nav-open'));});
            if(overlay)overlay.addEventListener('click',function(){setNav(false);});
            // Auto-close on nav link click (mobile) - exclude parent buttons
            document.querySelectorAll('.w2-nav-item, .w2-nav-sub').forEach(function(el){
                el.addEventListener('click',function(){ if(window.innerWidth<=860) setNav(false); });
            });
            // Sidebar group accordion (works on all screen sizes)
            document.querySelectorAll('.w2-nav-parent').forEach(function(btn){
                btn.addEventListener('click',function(e){
                    e.stopPropagation();
                    var grp=btn.closest('.w2-nav-group');
                    if(!grp)return;
                    var subs=grp.querySelector('.w2-nav-subs');
                    var isOpen=btn.classList.toggle('open');
                    grp.classList.toggle('open', isOpen);
                    if(subs)subs.style.display=isOpen?'block':'none';
                    var chev=btn.querySelector('.w2-nav-chev');
                    if(chev)chev.style.transform=isOpen?'rotate(180deg)':'';
                });
            });
        })();
        </script>
