<?php
/**
 * Mixed Farming Dashboard
 * Combined view of livestock + crops for mixed farms
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Mixed Farming Dashboard - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$today = date('Y-m-d');
$weekAgo = date('Y-m-d', strtotime('-7 days'));
$monthAgo = date('Y-m-d', strtotime('-30 days'));

// ═══════ LIVESTOCK DATA ═══════
$livestock = [];
$livestockSummary = [];
$cropsSummary = [];

if ($pdo) {
    try {
        // Animal counts by species
        $livestockSummary = $pdo->query("
            SELECT type AS species, COUNT(*) as count, 
                   SUM(CASE WHEN status IN ('Active','alive') THEN 1 ELSE 0 END) as active_count
            FROM animals 
            GROUP BY type 
            ORDER BY type
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Group counts by species
        $groupSummary = $pdo->query("
            SELECT species, SUM(head_count) as total_head, COUNT(*) as group_count
            FROM animal_groups 
            WHERE status='active'
            GROUP BY species 
            ORDER BY species
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Today's production
        $todayProduction = $pdo->query("
            SELECT species, 
                   SUM(eggs_collected) as eggs, 
                   SUM(milk_litres) as milk,
                   SUM(mortality) as mortality
            FROM production_records 
            WHERE record_date='$today'
            GROUP BY species
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Upcoming vaccinations (7 days)
        $upcomingVaccinations = $pdo->query("
            SELECT COUNT(*) as cnt
            FROM vaccinations 
            WHERE status='scheduled' 
            AND scheduled_date BETWEEN '$today' AND '" . date('Y-m-d', strtotime('+7 days')) . "'
        ")->fetchColumn();

        // Recent health alerts
        $recentHealth = $pdo->query("
            SELECT hr.*, a.name as animal_name, ag.name as group_name
            FROM health_records hr
            LEFT JOIN animals a ON hr.animal_id=a.id
            LEFT JOIN animal_groups ag ON hr.group_id=ag.id
            ORDER BY hr.created_at DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Housing utilization
        $housingUtil = $pdo->query("
            SELECT h.species, h.house_name, h.capacity,
                   (SELECT COUNT(*) FROM animal_groups ag WHERE ag.housing_id=h.id AND ag.status='active') as active_groups
            FROM houses h WHERE h.is_active=1
            ORDER BY h.species, h.house_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ═══════ CROP DATA ═══════
        // Active plantings
        $activePlantings = $pdo->query("
            SELECT cp.*, f.name as field_name, f.size_acres,
                   (SELECT SUM(cc.amount) FROM crop_costs cc WHERE cc.planting_id=cp.id) as total_cost
            FROM crop_plantings cp
            LEFT JOIN fields f ON f.id=cp.field_id
            WHERE cp.status='growing'
            ORDER BY cp.planting_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent harvests
        $recentHarvests = $pdo->query("
            SELECT ch.*, cp.crop, f.name as field_name
            FROM crop_harvests ch
            LEFT JOIN crop_plantings cp ON cp.id=ch.planting_id
            LEFT JOIN fields f ON f.id=cp.field_id
            ORDER BY ch.harvest_date DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Crop revenue
        $cropRevenue = $pdo->query("
            SELECT SUM(revenue) as total_revenue
            FROM crop_harvests
            WHERE harvest_date >= '$monthAgo'
        ")->fetchColumn();

        // Crop costs
        $cropCosts = $pdo->query("
            SELECT SUM(amount) as total_cost
            FROM crop_costs
            WHERE cost_date >= '$monthAgo'
        ")->fetchColumn();

        // Fields summary
        $fieldsSummary = $pdo->query("
            SELECT f.*, 
                   (SELECT COUNT(*) FROM crop_plantings cp WHERE cp.field_id=f.id AND cp.status='growing') as active_plantings
            FROM fields f
            ORDER BY f.name
        ")->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) { /* non-fatal */ }
}

// ═══════ COMBINED FINANCIAL SUMMARY ═══════
$totalLivestockValue = 0; // Would need market prices
$totalCropRevenue = (float)($cropRevenue ?? 0);
$totalCropCosts = (float)($cropCosts ?? 0);
$netCropProfit = $totalCropRevenue - $totalCropCosts;

// ═══════ DASHBOARD HTML ═══════
?>
<style>
    .mf-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .mf-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #E2E8F0; transition: all 0.3s ease; }
    .mf-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .mf-card h4 { margin: 0 0 12px; font-family: 'Outfit', sans-serif; font-size: 1rem; color: #0F172A; }
    .mf-card .value { font-size: 2rem; font-weight: 700; color: #22C55E; }
    .mf-card .label { font-size: 0.85rem; color: #64748B; margin-top: 4px; }
    .mf-section { background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #E2E8F0; margin-bottom: 24px; }
    .mf-section h3 { margin: 0 0 16px; font-family: 'Outfit', sans-serif; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; }
    .species-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
    .species-chicken { background: #FEF3C7; color: #D97706; }
    .species-cattle { background: #DBEAFE; color: #2563EB; }
    .species-goat { background: #F3E8FF; color: #9333EA; }
    .species-sheep { background: #E0E7FF; color: #4F46E5; }
    .species-pig { background: #FCE7F3; color: #DB2777; }
    .species-other { background: #F1F5F9; color: #64748B; }
    .progress-bar { height: 8px; background: #E2E8F0; border-radius: 4px; overflow: hidden; margin-top: 8px; }
    .progress-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
    .progress-green { background: linear-gradient(90deg, #22C55E, #16A34A); }
    .progress-blue { background: linear-gradient(90deg, #3B82F6, #2563EB); }
</style>

<div class="admin-page-header">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.4rem;">Mixed Farming Dashboard</h1>
        <p style="margin:4px 0 0;font-size:0.9rem;color:#64748B;">Combined overview of livestock and crop operations</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="hub_operations.php" class="btn btn-outline"><i data-lucide="paw-print" style="width:16px;height:16px;"></i> Livestock</a>
        <a href="hub_crops.php" class="btn btn-outline"><i data-lucide="wheat" style="width:16px;height:16px;"></i> Crops</a>
    </div>
</div>

<!-- ═══════ TOP STATS ═══════ -->
<div class="mf-grid">
    <?php
    $totalAnimals = array_sum(array_column($livestockSummary, 'active_count'));
    $totalHeads = array_sum(array_column($groupSummary, 'total_head'));
    $totalActivePlantings = count($activePlantings);
    $totalFields = count($fieldsSummary);
    ?>
    <div class="mf-card">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#22C55E,#16A34A);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="paw-print" style="width:24px;height:24px;color:#fff;"></i>
            </div>
            <div>
                <div class="value"><?= $totalAnimals + $totalHeads ?></div>
                <div class="label">Total Livestock</div>
            </div>
        </div>
    </div>
    <div class="mf-card">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#3B82F6,#2563EB);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="wheat" style="width:24px;height:24px;color:#fff;"></i>
            </div>
            <div>
                <div class="value"><?= $totalActivePlantings ?></div>
                <div class="label">Active Plantings</div>
            </div>
        </div>
    </div>
    <div class="mf-card">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#8B5CF6,#7C3AED);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="landmark" style="width:24px;height:24px;color:#fff;"></i>
            </div>
            <div>
                <div class="value"><?= $totalFields ?></div>
                <div class="label">Active Fields</div>
            </div>
        </div>
    </div>
    <div class="mf-card">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#F59E0B,#D97706);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="trending-up" style="width:24px;height:24px;color:#fff;"></i>
            </div>
            <div>
                <div class="value" style="color:<?= $netCropProfit >= 0 ? '#22C55E' : '#EF4444' ?>">KES <?= number_format($netCropProfit, 0) ?></div>
                <div class="label">Net Crop Profit (30d)</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════ LIVESTOCK OVERVIEW ═══════ -->
<div class="mf-section">
    <h3><i data-lucide="paw-print" style="width:20px;height:20px;color:#22C55E;"></i> Livestock Summary</h3>
    
    <?php if (!empty($livestockSummary) || !empty($groupSummary)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
        <?php
        $allSpecies = [];
        foreach ($livestockSummary as $ls) $allSpecies[$ls['species']] = ['individual' => $ls['active_count'], 'group' => 0, 'heads' => 0];
        foreach ($groupSummary as $gs) {
            if (!isset($allSpecies[$gs['species']])) $allSpecies[$gs['species']] = ['individual' => 0, 'group' => 0, 'heads' => 0];
            $allSpecies[$gs['species']]['group'] = $gs['group_count'];
            $allSpecies[$gs['species']]['heads'] = $gs['total_head'];
        }
        $speciesIcons = ['Chicken'=>'🐔','Cattle'=>'🐄','Goat'=>'🐐','Sheep'=>'🐑','Pig'=>'🐷','Rabbit'=>'🐰','Duck'=>'🦆','Turkey'=>'🦃'];
        foreach ($allSpecies as $sp => $data):
            $total = $data['individual'] + $data['heads'];
            $icon = $speciesIcons[$sp] ?? '🐾';
            $badgeClass = 'species-' . strtolower($sp);
        ?>
        <div style="background:#F8FAFC;border-radius:12px;padding:16px;border:1px solid #E2E8F0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span class="species-badge <?= $badgeClass ?>"><?= $icon ?> <?= $sp ?></span>
                <strong style="font-size:1.5rem;color:#0F172A;"><?= $total ?></strong>
            </div>
            <div style="font-size:0.8rem;color:#64748B;">
                <?= $data['individual'] ?> individual · <?= $data['group'] ?> groups · <?= $data['heads'] ?> in groups
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:#94a3b8;text-align:center;padding:20px;">No livestock data yet. <a href="hub_operations.php?tab=animals">Add animals</a> to get started.</p>
    <?php endif; ?>

    <!-- Today's Production -->
    <?php if (!empty($todayProduction)): ?>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid #E2E8F0;">
        <h4 style="margin:0 0 12px;font-size:0.95rem;color:#0F172A;">Today's Production</h4>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <?php foreach ($todayProduction as $tp): ?>
                <?php if ($tp['eggs'] > 0): ?>
                    <div style="background:#FEF3C7;border-radius:8px;padding:10px 16px;display:flex;align-items:center;gap:8px;">
                        <span style="font-size:1.2rem;">🥚</span>
                        <div><strong><?= number_format($tp['eggs']) ?></strong> <small>eggs</small></div>
                    </div>
                <?php endif; ?>
                <?php if ($tp['milk'] > 0): ?>
                    <div style="background:#DBEAFE;border-radius:8px;padding:10px 16px;display:flex;align-items:center;gap:8px;">
                        <span style="font-size:1.2rem;">🥛</span>
                        <div><strong><?= number_format($tp['milk'], 1) ?></strong> <small>litres milk</small></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════ CROP OVERVIEW ═══════ -->
<div class="mf-section">
    <h3><i data-lucide="wheat" style="width:20px;height:20px;color:#3B82F6;"></i> Crop Overview</h3>
    
    <?php if (!empty($activePlantings)): ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Crop</th><th>Field</th><th>Planted</th><th>Expected Harvest</th><th>Cost So Far</th><th>Cost/Acre</th></tr></thead>
            <tbody>
            <?php foreach ($activePlantings as $cp): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($cp['crop'], ENT_QUOTES) ?></strong></td>
                    <td><?= htmlspecialchars($cp['field_name'] ?? '—', ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($cp['planting_date'], ENT_QUOTES) ?></td>
                    <td><?= $cp['expected_harvest_date'] ? htmlspecialchars($cp['expected_harvest_date'], ENT_QUOTES) : '—' ?></td>
                    <td>KES <?= number_format((float)($cp['total_cost'] ?? 0), 0) ?></td>
                    <td><?= ($cp['size_acres'] > 0 && ($cp['total_cost'] ?? 0) > 0) ? 'KES ' . number_format((float)$cp['total_cost'] / (float)$cp['size_acres'], 0) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p style="color:#94a3b8;text-align:center;padding:20px;">No active plantings. <a href="hub_crops.php?tab=plantings">Add plantings</a> to get started.</p>
    <?php endif; ?>

    <!-- Recent Harvests -->
    <?php if (!empty($recentHarvests)): ?>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid #E2E8F0;">
        <h4 style="margin:0 0 12px;font-size:0.95rem;color:#0F172A;">Recent Harvests</h4>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <?php foreach ($recentHarvests as $rh): ?>
                <div style="background:#F0FDF4;border-radius:8px;padding:10px 16px;border:1px solid #BBF7D0;">
                    <strong><?= htmlspecialchars($rh['crop'], ENT_QUOTES) ?></strong>
                    <span style="margin-left:8px;color:#64748B;"><?= number_format((float)$rh['quantity'], 1) ?> <?= htmlspecialchars($rh['unit'], ENT_QUOTES) ?></span>
                    <span style="margin-left:8px;color:#22C55E;font-weight:600;">KES <?= number_format((float)$rh['revenue'], 0) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════ HOUSING UTILIZATION ═══════ -->
<div class="mf-section">
    <h3><i data-lucide="home" style="width:20px;height:20px;color:#8B5CF6;"></i> Housing by Species</h3>
    
    <?php if (!empty($housingUtil)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:12px;">
        <?php
        $grouped = [];
        foreach ($housingUtil as $h) $grouped[$h['species']][] = $h;
        foreach ($grouped as $sp => $houses):
            $icon = $speciesIcons[$sp] ?? '🏠';
        ?>
        <div style="background:#F8FAFC;border-radius:12px;padding:16px;border:1px solid #E2E8F0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <span style="font-size:1.2rem;"><?= $icon ?></span>
                <strong><?= htmlspecialchars($sp, ENT_QUOTES) ?></strong>
                <span style="margin-left:auto;color:#64748B;font-size:0.85rem;"><?= count($houses) ?> units</span>
            </div>
            <?php foreach ($houses as $h): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #E2E8F0;">
                    <span style="font-size:0.85rem;"><?= htmlspecialchars($h['house_name'], ENT_QUOTES) ?></span>
                    <span style="font-size:0.8rem;color:#64748B;"><?= ucfirst(str_replace('_', ' ', $h['house_type'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:#94a3b8;text-align:center;padding:20px;">No housing configured. <a href="hub_operations.php?tab=housing">Add housing</a> to get started.</p>
    <?php endif; ?>
</div>

<!-- ═══════ UPCOMING TASKS ═══════ -->
<div class="mf-section">
    <h3><i data-lucide="calendar-check" style="width:20px;height:20px;color:#F59E0B;"></i> Upcoming Tasks</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:16px;">
        <div style="background:#FEF3C7;border-radius:12px;padding:16px;border:1px solid #FCD34D;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <i data-lucide="syringe" style="width:18px;height:18px;color:#D97706;"></i>
                <strong>Vaccinations Due (7 days)</strong>
            </div>
            <div style="font-size:2rem;font-weight:700;color:#D97706;"><?= $upcomingVaccinations ?? 0 ?></div>
            <div style="font-size:0.85rem;color:#92400E;margin-top:4px;">scheduled vaccinations</div>
        </div>
        <div style="background:#DBEAFE;border-radius:12px;padding:16px;border:1px solid #93C5FD;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <i data-lucide="sprout" style="width:18px;height:18px;color:#2563EB;"></i>
                <strong>Expected Harvests</strong>
            </div>
            <div style="font-size:2rem;font-weight:700;color:#2563EB;"><?= count(array_filter($activePlantings, fn($p) => $p['expected_harvest_date'] && $p['expected_harvest_date'] <= date('Y-m-d', strtotime('+14 days')))) ?></div>
            <div style="font-size:0.85rem;color:#1E40AF;margin-top:4px;">within 14 days</div>
        </div>
        <?php if (!empty($recentHealth)): ?>
        <div style="background:#FEE2E2;border-radius:12px;padding:16px;border:1px solid #FCA5A5;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <i data-lucide="heart-pulse" style="width:18px;height:18px;color:#DC2626;"></i>
                <strong>Recent Health Alerts</strong>
            </div>
            <div style="font-size:0.85rem;color:#991B1B;margin-top:4px;">
                <?php foreach (array_slice($recentHealth, 0, 3) as $hr): ?>
                    <div style="padding:4px 0;border-bottom:1px solid #FECACA;">
                        <?= htmlspecialchars($hr['subject'] ?? $hr['record_type'] ?? 'Health event', ENT_QUOTES) ?>
                        <?php if ($hr['animal_name']): ?> — <?= htmlspecialchars($hr['animal_name'], ENT_QUOTES) ?><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
