<?php
/**
 * Admin page header and left navigation.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../includes/config.php';
}

if (!isset($page_title)) $page_title = 'Admin Console';
// Admin access check (Basic authentication for ANY admin area)
// Admin access check (Basic authentication for ANY admin area)
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager'], true)) {
    // Redirect to login if not authorized
    header('Location: /wangariadmin');
    exit;
}

// Authorization logic for specific roles
$isAdmin = in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager'], true);
$isStockManager = ($_SESSION['role'] ?? '') === 'stock_manager';

// Restrict Stock Manager to ONLY stock-related pages
if ($isStockManager) {
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
    $allowedStockPages = ['stock_dashboard.php', 'stock_calculator.php', 'stock_recipes.php', 'stock_costing.php', 'stock_alerts.php', 'incoming_stock.php', 'profile.php'];
    if (!in_array($currentPage, $allowedStockPages) && strpos($currentPage, 'stock_') === false && strpos($currentPage, 'incoming_') === false) {
        // Redirect stock managers away from non-stock pages (like orders, settings, users)
        header('Location: /Frontend/admin/stock_dashboard.php');
        exit;
    }
}

$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');
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
    <link rel="icon" type="image/svg+xml" href="/Frontend/images/wangari-mark.svg">
    <style>
        :root {
            --admin-primary: #166534;
            --admin-primary-light: #1B7A3D;
            --admin-accent: #D0F24C;
            --admin-accent-dark: #9DBF2E;
            --admin-dark: #0B1220;
            --admin-sidebar-bg: #0B1220;
            --admin-body-bg: #F4F5F8;
            --admin-border: rgba(203, 213, 225, 0.8);
            --admin-card-bg: #ffffff;
            --admin-text-main: #1e293b;
            --admin-text-heading: #0f172a;
        }

        body.admin-layout { 
            background: var(--admin-body-bg); 
            color: var(--admin-text-main);
            font-family: 'Inter Tight', sans-serif;
            margin: 0;
            padding: 0;
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
            display: flex; 
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
            scrollbar-color: rgba(208, 242, 76, 0.35) transparent;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(208, 242, 76, 0.35);
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
            box-shadow: 0 4px 16px rgba(208, 242, 76, 0.25);
        }

        .admin-sidebar-nav .sidebar-dropdown a {
            color: #9CA3AF;
        }

        .admin-sidebar-nav .sidebar-dropdown a:hover {
            color: var(--admin-accent);
            background: rgba(255,255,255,0.06);
        }

        .admin-sidebar-nav .sidebar-dropdown a.active {
            background: rgba(208, 242, 76, 0.12);
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
            flex: 1; 
            padding: 24px; 
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        /* Top utility bar */
        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            padding: 12px 20px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
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
            border-radius: 4px; 
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03); 
            padding: 20px;
            box-sizing: border-box;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .admin-card:hover {
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
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
            grid-template-columns: repeat(3, minmax(0, 1fr)); 
            gap: 20px; 
            margin-bottom: 32px;
        }

        .stat-card { 
            padding: 24px; 
            border-radius: 4px; 
            background: var(--admin-card-bg); 
            border: 1px solid var(--admin-border); 
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03); 
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
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
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            background: rgba(27, 94, 32, 0.06);
            color: var(--admin-primary);
            display: flex;
            align-items: center;
            justify-content: center;
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
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .admin-table th {
            padding: 16px 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--admin-border);
            background: var(--admin-body-bg);
        }

        .admin-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--admin-border);
            font-size: 0.95rem;
            color: var(--admin-text-main);
        }

        .admin-table tr:hover td {
            background: rgba(248, 250, 252, 0.6);
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 2px;
            font-size: 0.75rem;
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
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            box-sizing: border-box;
        }

        .admin-form-control:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(27, 94, 32, 0.15);
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
            gap: 7px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
            border: 1px solid transparent;
            text-decoration: none;
            white-space: nowrap;
            line-height: 1;
        }

        .admin-layout .btn i,
        .admin-layout .btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* Small variant used for table row actions */
        .admin-layout .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            gap: 5px;
            border-radius: 5px;
        }

        .admin-layout .btn-sm i,
        .admin-layout .btn-sm svg {
            width: 14px;
            height: 14px;
        }

        /* Primary, green */
        .admin-layout .btn-primary {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-light) 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(27,94,32,0.2);
        }

        .admin-layout .btn-primary:hover {
            background: linear-gradient(135deg, #145214 0%, var(--admin-primary) 100%);
            box-shadow: 0 4px 16px rgba(27,94,32,0.3);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .admin-layout .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(27,94,32,0.2);
        }

        /* Outline, border only */
        .admin-layout .btn-outline {
            background: transparent;
            border: 1.5px solid var(--admin-border);
            color: #475569;
        }

        .admin-layout .btn-outline:hover {
            background: rgba(27,94,32,0.06);
            border-color: var(--admin-primary);
            color: var(--admin-primary);
            transform: translateY(-1px);
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
            --w2-lime: #D0F24C;
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
            background: linear-gradient(180deg, #0B1220 0%, #0E1B2E 100%);
            border-right: 1px solid rgba(255,255,255,0.06);
            color: #C7CDD8;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 100;
            box-sizing: border-box;
        }
        .w2-side-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 20px 20px 16px;
        }
        .w2-logo {
            height: 42px;
            width: auto;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(208, 242, 76, 0.12);
        }
        .w2-brand-name {
            margin: 0;
            font-family: 'Inter Tight', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }
        .w2-brand-sub {
            display: block;
            color: rgba(255,255,255,0.4);
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
            scrollbar-color: rgba(208,242,76,0.25) transparent;
        }
        .w2-nav-section {
            margin: 18px 8px 7px;
            font-size: 0.66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255,255,255,0.32);
        }
        .w2-nav-section:first-child { margin-top: 4px; }
        .w2-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #AEB6C4;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
            margin-bottom: 2px;
        }
        .w2-nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }
        .w2-nav-item.active {
            background: linear-gradient(135deg, rgba(208,242,76,0.16), rgba(208,242,76,0.05));
            color: var(--w2-lime);
            box-shadow: inset 0 0 0 1px rgba(208,242,76,0.25);
        }
        .w2-nav-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .w2-nav-badge {
            margin-left: auto;
            background: var(--w2-lime);
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
            color: #AEB6C4;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: inherit;
            cursor: pointer;
            text-align: left;
            transition: all 0.18s;
        }
        .w2-nav-parent:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .w2-nav-parent.open { color: var(--w2-lime); }
        .w2-nav-chev {
            margin-left: auto;
            width: 15px;
            height: 15px;
            transition: transform 0.25s ease;
        }
        .w2-nav-parent.open .w2-nav-chev { transform: rotate(180deg); }
        .w2-nav-subs {
            padding: 4px 0 6px 26px;
            border-left: 2px solid rgba(208,242,76,0.18);
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
        .w2-nav-sub:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .w2-nav-sub.active { color: var(--w2-lime); font-weight: 700; }
        .w2-nav-sub.active span { opacity: 1; }

        .w2-side-foot {
            padding: 14px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }
        .w2-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            margin-bottom: 10px;
        }
        .w2-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #14532D, #D0F24C);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-family: 'Inter Tight', sans-serif;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .w2-user-meta p { margin: 0; font-size: 0.85rem; font-weight: 700; color: #fff; }
        .w2-user-meta span { font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: capitalize; }
        .w2-signout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border-radius: 10px;
            background: rgba(220,38,38,0.1);
            color: #FCA5A5;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.18s;
        }
        .w2-signout:hover { background: rgba(220,38,38,0.2); color: #FECACA; }

        /* Hide legacy sidebar if any page still includes it */
        .admin-shell > nav:not(.w2-side) { display: none !important; }

        /* ── Responsive polish ── */
        @media (max-width: 860px) {
            .admin-card { padding: 16px !important; }
            .admin-content { padding: 14px !important; }
            .w2-side { width: 220px; }
        }
    </style>

    <script>
        // Dynamically hide sidebar and topbar elements if loaded inside an iframe
        if (window.self !== window.top) {
            document.documentElement.classList.add('in-iframe');
            const style = document.createElement('style');
            style.textContent = '.admin-shell > nav, .admin-top-bar { display: none !important; } .admin-shell { display: block !important; } .admin-content { padding: 0 !important; }';
            document.head.appendChild(style);
        }
    </script>
</head>
<body class="admin-layout">
<script>
    window.WangariAdmin = window.WangariAdmin || {};
    window.WangariAdmin.csrfToken = <?php echo json_encode($csrf_token); ?>;
</script>
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
                <button id="open-system-guide" title="System Walkthrough Guide" style="background:#F4F6F9;border:1px solid var(--w2-border);cursor:pointer;color:var(--w2-primary);display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;transition:all 0.2s;outline:none;" onmouseover="this.style.background='#E8F5EC'" onmouseout="this.style.background='#F4F6F9'">
                    <i data-lucide="help-circle" style="width:20px;height:20px;"></i>
                </button>
                <a href="/Frontend/admin/ai_assistant.php" title="Ask Wangari AI" style="background:#F4F6F9;border:1px solid var(--w2-border);color:var(--w2-primary);display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#E8F5EC'" onmouseout="this.style.background='#F4F6F9'">
                    <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
                </a>
                <div class="admin-profile-badge" style="display:flex;align-items:center;gap:10px;background:#F8FAFC;border:1px solid var(--w2-border);border-radius:999px;padding:5px 14px 5px 6px;">
                    <div class="admin-avatar" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#14532D,#D0F24C);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-family:'Inter Tight',sans-serif;font-size:0.9rem;">
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
