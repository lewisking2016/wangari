<?php
/**
 * Consolidated Admin - Livestock & Poultry Operations
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Livestock & Poultry - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href = '/Frontend/pages/login.php';</script>";
    exit;
}

$tab = $_GET['tab'] ?? 'flocks';
$allowedTabs = ['flocks', 'animals', 'herds', 'breeding', 'health'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'flocks';
}
?>

<!-- Tab Selector Navigation -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid var(--admin-border); padding-bottom: 12px; flex-wrap: wrap;">
    <a href="?tab=flocks" class="btn <?php echo $tab === 'flocks' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="bird" style="width:16px; height:16px;"></i> Flocks (Poultry)
    </a>
    <a href="?tab=animals" class="btn <?php echo $tab === 'animals' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="paw-print" style="width:16px; height:16px;"></i> Animals
    </a>
    <a href="?tab=herds" class="btn <?php echo $tab === 'herds' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="grid" style="width:16px; height:16px;"></i> Herds
    </a>
    <a href="?tab=breeding" class="btn <?php echo $tab === 'breeding' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="dna" style="width:16px; height:16px;"></i> Breeding
    </a>
    <a href="?tab=health" class="btn <?php echo $tab === 'health' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="heart-pulse" style="width:16px; height:16px;"></i> Health & Medical
    </a>
</div>

<!-- Dynamic Container for Active Tab Content -->
<div class="operations-tab-content">
    <?php
    switch ($tab) {
        case 'flocks':
            // Include flock manager logic & view inline
            $path_prefix = '../../';
            include __DIR__ . '/flocks_tab.php';
            break;
        case 'animals':
            include __DIR__ . '/animals_tab.php';
            break;
        case 'herds':
            include __DIR__ . '/herds_tab.php';
            break;
        case 'breeding':
            include __DIR__ . '/breeding_tab.php';
            break;
        case 'health':
            include __DIR__ . '/health_tab.php';
            break;
    }
    ?>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
<?php include __DIR__ . '/includes/admin_footer.php'; ?>
