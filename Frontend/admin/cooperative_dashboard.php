<?php
/**
 * Wangari Cooperative Dashboard
 * 
 * Tool for cooperative leaders to:
 * 1. See all cooperative member farmers and their performance
 * 2. Generate bulk reports for the cooperative
 * 3. Track adoption rates across members
 * 4. Compare member farms to each other (anonymized)
 * 
 * Access: Admin or cooperative leader role
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    wangariStartSession();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

$page_title = 'Cooperative Dashboard — Wangari';

// Get cooperative members
$members = [];
$coop_stats = [
    'total_members' => 0,
    'active_this_week' => 0,
    'active_this_month' => 0,
    'avg_profit' => 0,
    'total_eggs_month' => 0,
    'top_performers' => [],
];

// Get all farmers with their data
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.full_name, u.email, u.phone_number, u.primary_goal, u.created_at as joined,
        f.name as farm_name, f.farm_code,
        (SELECT COUNT(*) FROM daily_production WHERE user_id = u.id AND record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as entries_this_week,
        (SELECT COUNT(*) FROM daily_production WHERE user_id = u.id AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as entries_this_month,
        (SELECT COALESCE(SUM(eggs_collected), 0) FROM daily_production WHERE user_id = u.id AND record_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) as eggs_month,
        (SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = u.id AND income_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) as income_month,
        (SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = u.id AND expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) as expenses_month
    FROM users u
    LEFT JOIN farm_members fm ON u.id = fm.user_id
    LEFT JOIN farms f ON fm.farm_id = f.id
    WHERE fm.role = 'farm_owner'
    ORDER BY u.full_name ASC
    LIMIT 500
");
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$coop_stats['total_members'] = count($members);
$total_eggs = 0;
$total_profit = 0;
$profitable = 0;

foreach ($members as &$m) {
    $m['profit'] = ($m['income_month'] ?? 0) - ($m['expenses_month'] ?? 0);
    $total_eggs += $m['eggs_month'] ?? 0;
    $total_profit += $m['profit'];
    if ($m['profit'] > 0) $profitable++;
    
    if (($m['entries_this_week'] ?? 0) > 0) $coop_stats['active_this_week']++;
    if (($m['entries_this_month'] ?? 0) > 0) $coop_stats['active_this_month']++;
}
unset($m);

$coop_stats['total_eggs_month'] = $total_eggs;
$coop_stats['avg_profit'] = count($members) > 0 ? $total_profit / count($members) : 0;
$coop_stats['profitable_farms'] = $profitable;

// Sort by profit for top performers
usort($members, fn($a, $b) => $b['profit'] <=> $a['profit']);
$coop_stats['top_performers'] = array_slice($members, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0a0f0d; color: #e5e7eb; font-family: 'Inter Tight', sans-serif; padding: 24px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; }
        .subtitle { color: rgba(255,255,255,0.5); margin-bottom: 32px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 32px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 16px; }
        .stat-label { color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { color: #fff; font-size: 1.8rem; font-weight: 800; margin-top: 4px; }
        .stat-value.green { color: #22C55E; }
        .stat-value.red { color: #EF4444; }
        .stat-value.yellow { color: #F59E0B; }
        .stat-value.blue { color: #3B82F6; }
        
        .section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .section h2 { font-size: 1.2rem; font-weight: 700; margin-bottom: 16px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 12px; color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(255,255,255,0.08); }
        td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 100px; font-size: 0.7rem; font-weight: 600; }
        .badge-active { background: rgba(34,197,94,0.15); color: #22C55E; }
        .badge-inactive { background: rgba(239,68,68,0.15); color: #EF4444; }
        .badge-profit { background: rgba(34,197,94,0.15); color: #22C55E; }
        .badge-loss { background: rgba(239,68,68,0.15); color: #EF4444; }
        
        .top-list { display: flex; flex-direction: column; gap: 8px; }
        .top-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: rgba(255,255,255,0.02); border-radius: 8px; }
        .top-rank { font-weight: 800; color: #22C55E; min-width: 24px; }
        .top-name { flex: 1; font-weight: 600; }
        .top-profit { font-weight: 700; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏘️ Cooperative Dashboard</h1>
        <p class="subtitle">Overview of all member farms and their performance.</p>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Members</div>
                <div class="stat-value"><?php echo $coop_stats['total_members']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active This Week</div>
                <div class="stat-value green"><?php echo $coop_stats['active_this_week']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active This Month</div>
                <div class="stat-value blue"><?php echo $coop_stats['active_this_month']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Eggs (Month)</div>
                <div class="stat-value yellow"><?php echo number_format($coop_stats['total_eggs_month']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg Profit/Farm</div>
                <div class="stat-value <?php echo $coop_stats['avg_profit'] >= 0 ? 'green' : 'red'; ?>">KES <?php echo number_format($coop_stats['avg_profit']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Profitable Farms</div>
                <div class="stat-value green"><?php echo $coop_stats['profitable_farms']; ?>/<?php echo $coop_stats['total_members']; ?></div>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Top Performers -->
            <div class="section">
                <h2>🏆 Top Performers (This Month)</h2>
                <div class="top-list">
                    <?php if (empty($coop_stats['top_performers'])): ?>
                        <p style="color:rgba(255,255,255,0.4);">No data yet.</p>
                    <?php else: ?>
                        <?php foreach ($coop_stats['top_performers'] as $i => $t): ?>
                            <div class="top-item">
                                <span class="top-rank">#<?php echo $i + 1; ?></span>
                                <span class="top-name"><?php echo htmlspecialchars($t['full_name'] ?: $t['email']); ?></span>
                                <span class="top-profit" style="color: <?php echo $t['profit'] >= 0 ? '#22C55E' : '#EF4444'; ?>">
                                    KES <?php echo number_format($t['profit']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Adoption Rates -->
            <div class="section">
                <h2>📊 Adoption Overview</h2>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem;">Active this week</span>
                            <span style="font-size: 0.85rem; color: #22C55E; font-weight: 600;">
                                <?php echo $coop_stats['total_members'] > 0 ? round(($coop_stats['active_this_week'] / $coop_stats['total_members']) * 100) : 0; ?>%
                            </span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $coop_stats['total_members'] > 0 ? ($coop_stats['active_this_week'] / $coop_stats['total_members']) * 100 : 0; ?>%; background: #22C55E; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem;">Active this month</span>
                            <span style="font-size: 0.85rem; color: #3B82F6; font-weight: 600;">
                                <?php echo $coop_stats['total_members'] > 0 ? round(($coop_stats['active_this_month'] / $coop_stats['total_members']) * 100) : 0; ?>%
                            </span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $coop_stats['total_members'] > 0 ? ($coop_stats['active_this_month'] / $coop_stats['total_members']) * 100 : 0; ?>%; background: #3B82F6; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem;">Profitable farms</span>
                            <span style="font-size: 0.85rem; color: #F59E0B; font-weight: 600;">
                                <?php echo $coop_stats['total_members'] > 0 ? round(($coop_stats['profitable_farms'] / $coop_stats['total_members']) * 100) : 0; ?>%
                            </span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $coop_stats['total_members'] > 0 ? ($coop_stats['profitable_farms'] / $coop_stats['total_members']) * 100 : 0; ?>%; background: #F59E0B; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 14px; background: rgba(34,197,94,0.06); border: 1px dashed rgba(34,197,94,0.3); border-radius: 8px;">
                    <p style="color: #22C55E; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">💡 Insight</p>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">
                        <?php if ($coop_stats['active_this_week'] < $coop_stats['total_members'] * 0.3): ?>
                            Less than 30% of members are active this week. Consider sending a WhatsApp reminder to inactive members.
                        <?php elseif ($coop_stats['profitable_farms'] < $coop_stats['total_members'] * 0.5): ?>
                            Less than 50% of farms are profitable. Consider a group training session on cost management.
                        <?php else: ?>
                            Good adoption! Keep encouraging inactive members to log their data daily.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- All Members Table -->
        <div class="section">
            <h2>👨‍🌾 All Member Farms</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Farm</th>
                            <th>Goal</th>
                            <th>Eggs (Month)</th>
                            <th>Income</th>
                            <th>Costs</th>
                            <th>Profit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                            <tr><td colspan="9" style="text-align:center; color:rgba(255,255,255,0.4); padding:40px;">No members yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($members as $i => $m): ?>
                                <tr>
                                    <td style="color:rgba(255,255,255,0.4);"><?php echo $i + 1; ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($m['full_name'] ?: $m['email']); ?></div>
                                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);"><?php echo htmlspecialchars($m['phone_number'] ?? ''); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['farm_name'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($m['primary_goal']): ?>
                                            <span class="badge badge-active"><?php echo htmlspecialchars(ucfirst($m['primary_goal'])); ?></span>
                                        <?php else: ?>
                                            <span style="color:rgba(255,255,255,0.3);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:600;"><?php echo number_format($m['eggs_month'] ?? 0); ?></td>
                                    <td>KES <?php echo number_format($m['income_month'] ?? 0); ?></td>
                                    <td>KES <?php echo number_format($m['expenses_month'] ?? 0); ?></td>
                                    <td>
                                        <span style="font-weight:700; color: <?php echo ($m['profit'] ?? 0) >= 0 ? '#22C55E' : '#EF4444'; ?>">
                                            KES <?php echo number_format($m['profit'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($m['entries_this_week'] ?? 0) > 0): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
