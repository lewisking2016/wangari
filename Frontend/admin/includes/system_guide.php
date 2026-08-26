<?php
/**
 * System Walkthrough Guide — every module, every flow.
 *
 * Rendered as a modal from $guideSections below. Each module entry:
 *   id     — stable key (used for prev/next + auto-detect)
 *   pages  — script basenames that auto-open this entry
 *   icon   — lucide icon name
 *   title  — heading
 *   summary— one or two sentences about what the module does
 *   steps  — the flow, numbered (supports inline <strong>/<em>)
 *   tips   — "good to know" bullet points
 *
 * Grouped to mirror the admin sidebar so the guide stays in sync with
 * what the user actually sees in navigation.
 */
declare(strict_types=1);

$guideSections = [
    'Getting Started' => [
        [
            'id' => 'dashboard', 'pages' => ['dashboard.php'], 'icon' => 'layout-dashboard',
            'title' => 'Dashboard — Farm Overview',
            'summary' => 'Your landing screen after login. A live snapshot of the whole farm: money in and out, customer credit, egg production, mortality, low-stock warnings and pending orders.',
            'steps' => [
                'Read the overview cards at the top: money in today, money out today, credit owed, overdue credit, eggs produced, mortality, low-stock items and pending orders.',
                'Check the charts for sales and production trends.',
                'Review the recent orders list and update statuses.',
                'Click any card to jump into the relevant module.',
            ],
            'tips' => [
                'The dashboard is read-only — all changes happen inside the modules.',
                'If you see a permission notice, ask the Farm Manager to grant access under Settings.',
            ],
        ],
        [
            'id' => 'ai-assistant', 'pages' => ['ai_assistant.php'], 'icon' => 'sparkles',
            'title' => 'Ask Wangari AI',
            'summary' => 'Your personal farm AI that reads your records, does calculations, and gives farming advice.',
            'steps' => [
                'Click Ask Wangari AI in the sidebar or top bar.',
                'Type your question about farm data, calculations, or farming knowledge.',
                'Try suggestions like: How much did I sell? Who owes me credit?',
            ],
            'tips' => [
                'You get 40 queries per day on free trial and Pro plan. Plus gets unlimited.',
                'Ask farming questions: vaccination schedules, disease treatment, feeding rates.',
            ],
        ],
        [
            'id' => 'branches', 'pages' => ['hub_branches.php'], 'icon' => 'git-branch',
            'title' => 'Farm Branches',
            'summary' => 'Manage multiple farm locations from one account.',
            'steps' => [
                'Click + Add Branch.',
                'Enter branch name, location, and type.',
                'Use the branch switcher in the sidebar to switch between branches.',
            ],
            'tips' => [
                'Each branch has its own records. All data changes when you switch.',
            ],
        ],
    ],
    'Poultry Operations' => [
        [
            'id' => 'flocks', 'pages' => ['hub_operations.php'], 'icon' => 'bird',
            'title' => 'Flocks',
            'summary' => 'Create and manage flocks (layers, broilers, breeders) with batch number, breed, count, and status.',
            'steps' => [
                'Click + Add Flock to create a new batch.',
                'Enter batch number, breed, count, start date, house, and status.',
                'Click Save.',
            ],
            'tips' => [
                'One flock = one batch. Broilers go from day-old to market in 6-8 weeks.',
                'Layers stay in production for 12-18 months.',
            ],
        ],
        [
            'id' => 'production', 'pages' => ['hub_operations.php'], 'icon' => 'egg',
            'title' => 'Daily Production',
            'summary' => 'Record eggs collected, mortality, feed consumed, and weight gain per flock per day.',
            'steps' => [
                'Click + Log Production.',
                'Select the flock, date, eggs collected, mortality, feed used, weight.',
                'Click Save.',
                'Check FCR and HDP in the Reports tab.',
            ],
            'tips' => [
                'Log daily for accurate FCR and HDP calculations.',
                'HDP = eggs / hens / days. Target is 80%+.',
            ],
        ],
        [
            'id' => 'vaccinations', 'pages' => ['hub_operations.php'], 'icon' => 'syringe',
            'title' => 'Vaccinations',
            'summary' => 'Schedule and track vaccinations with built-in guides for poultry diseases.',
            'steps' => [
                'Click + Schedule Vaccine.',
                'Enter date, flock, vaccine, dosage, cost, notes.',
                'Click Save.',
                'Mark as Administered when done.',
            ],
            'tips' => [
                'Built-in guides cover Newcastle, Gumboro, Fowl Pox, Mareks.',
                'Upcoming vaccinations appear on the dashboard.',
            ],
        ],
        [
            'id' => 'batches', 'pages' => ['hub_operations.php'], 'icon' => 'layers',
            'title' => 'Batches and Houses',
            'summary' => 'Track batches across houses/coops and manage occupancy rates.',
            'steps' => [
                'View the Batches tab to see all active batches.',
                'Switch to Houses tab to manage coops, barns, and pens.',
            ],
            'tips' => [
                'Each house has a max capacity. The system warns when full.',
            ],
        ],
        [
            'id' => 'health', 'pages' => ['hub_operations.php'], 'icon' => 'heart-pulse',
            'title' => 'Health and Vet',
            'summary' => 'Log sickness, treatments, and vet visits with medicine, costs, and recovery status.',
            'steps' => [
                'Click + Log Health Record.',
                'Enter date, flock, symptoms, diagnosis, medicine, vet, cost.',
                'Click Save.',
            ],
            'tips' => [
                'Log every health event to spot disease patterns early.',
            ],
        ],
        [
            'id' => 'broiler', 'pages' => ['hub_operations.php'], 'icon' => 'workflow',
            'title' => 'Broiler Workflow',
            'summary' => 'Step-by-step broiler tracking from day-old chick to market-ready bird.',
            'steps' => [
                'Start a broiler batch from Poultry Tools.',
                'Log daily feed, water, and mortality.',
                'Track weight gains against growth curves.',
            ],
            'tips' => [
                'Compare actual FCR vs target FCR for each batch.',
            ],
        ],
        [
            'id' => 'hatchery', 'pages' => ['hub_operations.php'], 'icon' => 'egg',
            'title' => 'Hatchery (DOC)',
            'summary' => 'Track day-old chick arrivals, eggs set, hatched, and hatch rate.',
            'steps' => [
                'Log DOC arrivals with supplier, breed, count, cost.',
                'Record eggs set and hatched.',
                'Track hatch rate percentages.',
            ],
            'tips' => [
                'Good hatch rates (85%+) indicate healthy breeding stock.',
            ],
        ],
        [
            'id' => 'feeding', 'pages' => ['hub_operations.php'], 'icon' => 'wheat',
            'title' => 'Feeding Program',
            'summary' => 'Create feed schedules per flock, track consumption, and monitor feed costs.',
            'steps' => [
                'Click + Create Feed Program.',
                'Select flock, feed type, quantity, schedule.',
                'Log actual feed consumed daily.',
            ],
            'tips' => [
                'Feed is 60-70% of poultry costs. Track carefully.',
            ],
        ],
        [
            'id' => 'losses', 'pages' => ['hub_operations.php'], 'icon' => 'activity',
            'title' => 'Losses and Quality',
            'summary' => 'Track bird losses and egg quality grading.',
            'steps' => [
                'Log losses with cause, count, and date.',
                'Grade eggs by size and quality (A, B, C).',
            ],
            'tips' => [
                'Target mortality under 5% for broilers, under 10% for layers.',
            ],
        ],
    ],
    'Inventory and Stores' => [
        [
            'id' => 'products', 'pages' => ['hub_inventory.php'], 'icon' => 'package',
            'title' => 'Products Catalog',
            'summary' => 'Manage all farm products with SKUs, prices, and stock levels.',
            'steps' => [
                'Click + Add Product.',
                'Enter name, category, SKU, unit, cost price, selling price, min stock.',
                'Click Save.',
            ],
            'tips' => [
                'Set min stock levels for automatic low-stock alerts.',
                'Use categories: Feed, Medicine, Produce, Equipment, Supplies.',
            ],
        ],
        [
            'id' => 'stores', 'pages' => ['hub_inventory.php'], 'icon' => 'warehouse',
            'title' => 'Stores and Stock',
            'summary' => 'Track stock movements between stores with in/out logs.',
            'steps' => [
                'View current stock levels in each store.',
                'Log stock movements (transfer, purchase, consumption).',
                'Reconcile physical count vs system count.',
            ],
            'tips' => [
                'Regular stock reconciliation prevents losses and theft.',
            ],
        ],
        [
            'id' => 'feed-production', 'pages' => ['hub_inventory.php'], 'icon' => 'flask-conical',
            'title' => 'Feed Production',
            'summary' => 'Track on-farm feed mixing: recipes, ingredients, batch production, and cost per kg.',
            'steps' => [
                'Create a feed recipe with ingredients and proportions.',
                'Log a production batch with quantities mixed.',
                'The system calculates cost per kg automatically.',
            ],
            'tips' => [
                'On-farm feed mixing can reduce costs by 20-30%.',
            ],
        ],
        [
            'id' => 'egg-grading', 'pages' => ['hub_inventory.php'], 'icon' => 'egg',
            'title' => 'Egg Grading',
            'summary' => 'Grade eggs by size and quality. Track graded vs ungraded stock.',
            'steps' => [
                'Log egg grading sessions with counts per grade (A, B, C).',
                'View graded stock levels and pricing per grade.',
            ],
            'tips' => [
                'Grade A eggs command premium prices.',
            ],
        ],
    ],
    'Sales and Finance' => [
        [
            'id' => 'sales-finance', 'pages' => ['hub_finance.php'], 'icon' => 'trending-up',
            'title' => 'Sales and Finance Hub',
            'summary' => 'Central finance view: sales, revenue, credit, and transactions.',
            'steps' => [
                'Check summary cards: today sales, month total, credit owed.',
                'Review the transaction list for recent activity.',
                'Use quick-action buttons to add sales, payments, or expenses.',
            ],
            'tips' => [
                'This hub gives a snapshot. Drill into sub-tabs for details.',
            ],
        ],
        [
            'id' => 'lpo', 'pages' => ['hub_finance.php'], 'icon' => 'file-text',
            'title' => 'LPO and Invoicing',
            'summary' => 'Generate Local Purchase Orders and invoices for formal transactions.',
            'steps' => [
                'Click + Create LPO or + Create Invoice.',
                'Select customer, add items with quantities and prices.',
                'Generate PDF for printing or email.',
            ],
            'tips' => [
                'LPOs are standard for government and institutional buyers in Kenya.',
            ],
        ],
        [
            'id' => 'cost-profit', 'pages' => ['hub_finance.php'], 'icon' => 'calculator',
            'title' => 'Costs and Profit',
            'summary' => 'See true costs and profits: batch profitability, FCR analysis, and margin reports.',
            'steps' => [
                'View the Profit Summary cards.',
                'Check Batch Profitability for each flock.',
                'Review FCR and HDP metrics.',
                'Export reports as PDF or CSV.',
            ],
            'tips' => [
                'FCR = feed consumed / weight gained. Lower is better.',
                'HDP = eggs / hens / days. Target is 80%+.',
            ],
        ],
        [
            'id' => 'cashbook', 'pages' => ['hub_finance.php'], 'icon' => 'book-open',
            'title' => 'Cashbook (Money Book)',
            'summary' => 'Daily cash-in and cash-out tracking.',
            'steps' => [
                'Open the Cashbook tab.',
                'Log daily income and expenses.',
                'The running balance shows your cash position.',
            ],
            'tips' => [
                'Update daily for accurate cash flow visibility.',
            ],
        ],
        [
            'id' => 'customer-credit', 'pages' => ['hub_finance.php'], 'icon' => 'credit-card',
            'title' => 'Customer Credit',
            'summary' => 'Track credit sales and who owes you money.',
            'steps' => [
                'View the credit list showing outstanding balances.',
                'Record credit payments as they come in.',
            ],
            'tips' => [
                'Overdue credit shows in red on the dashboard.',
            ],
        ],
        [
            'id' => 'procurement', 'pages' => ['hub_finance.php'], 'icon' => 'truck',
            'title' => 'Procurement',
            'summary' => 'Manage purchase orders for farm supplies from suppliers.',
            'steps' => [
                'Click + Create Purchase Order.',
                'Select supplier, add items, quantities, prices.',
                'Track delivery status.',
            ],
            'tips' => [
                'Use procurement to negotiate better supplier prices.',
            ],
        ],
        [
            'id' => 'reconciliation', 'pages' => ['hub_finance.php'], 'icon' => 'scale',
            'title' => 'Daily Reconciliation',
            'summary' => 'Match your cashbook against actual cash on hand.',
            'steps' => [
                'Open the Reconciliation tab.',
                'Enter your physical cash count.',
                'The system compares against the calculated balance.',
            ],
            'tips' => [
                'Reconcile daily or at minimum weekly.',
            ],
        ],
        [
            'id' => 'bulk-sales', 'pages' => ['hub_finance.php'], 'icon' => 'shopping-cart',
            'title' => 'Bulk Sales and Walk-in',
            'summary' => 'Handle walk-in customers and bulk orders without creating permanent records.',
            'steps' => [
                'Click + Quick Sale.',
                'Enter customer name (optional), items, amount, payment method.',
                'Complete the sale.',
            ],
            'tips' => [
                'Good for market-day sales with many different buyers.',
            ],
        ],
    ],
    'Reports and Tools' => [
        [
            'id' => 'analytics', 'pages' => ['hub_finance.php'], 'icon' => 'bar-chart-3',
            'title' => 'Analytics and Charts',
            'summary' => 'Visual analytics: sales trends, production graphs, expense breakdowns.',
            'steps' => [
                'Open the Analytics tab in Sales and Finance.',
                'Filter by date range, flock, or category.',
                'Export charts as images for presentations.',
            ],
            'tips' => [
                'Use analytics for monthly business reviews.',
            ],
        ],
        [
            'id' => 'import-export', 'pages' => ['bulk_import_export.php'], 'icon' => 'file-up',
            'title' => 'Bulk Import / Export',
            'summary' => 'Import data from Excel/CSV or export farm data for backup.',
            'steps' => [
                'Go to Import tab, download the template.',
                'Fill in your data, upload the file.',
                'Go to Export tab to download any module data.',
            ],
            'tips' => [
                'Export regularly for backups.',
            ],
        ],
    ],
    'Team and Messages' => [
        [
            'id' => 'staff', 'pages' => ['hub_people.php'], 'icon' => 'users',
            'title' => 'Staff',
            'summary' => 'Manage farm staff with names, roles, phones, wages, and status.',
            'steps' => [
                'Click + Add Staff.',
                'Enter name, role, phone, daily wage, hire date.',
                'Click Save.',
            ],
            'tips' => [
                'Workers can log in with their own accounts using a connection code.',
            ],
        ],
        [
            'id' => 'user-accounts', 'pages' => ['hub_people.php'], 'icon' => 'key-round',
            'title' => 'User Accounts',
            'summary' => 'Manage login accounts for team members with roles and access levels.',
            'steps' => [
                'Click + Add Staff Member.',
                'Enter name, username, email, role.',
                'Click Save.',
            ],
            'tips' => [
                'Farm Managers only see accounts they created.',
                'Super Admin is hidden from farm-level views.',
            ],
        ],
        [
            'id' => 'tasks', 'pages' => ['hub_people.php'], 'icon' => 'list-checks',
            'title' => 'Tasks',
            'summary' => 'Create and assign tasks to team members.',
            'steps' => [
                'Click + Assign Task.',
                'Enter title, description, assign to, due date, priority.',
                'Workers see their tasks when they log in.',
            ],
            'tips' => [
                'Use high priority for urgent tasks.',
            ],
        ],
        [
            'id' => 'messages', 'pages' => ['hub_people.php'], 'icon' => 'message-square',
            'title' => 'Messages',
            'summary' => 'Send messages to team members.',
            'steps' => [
                'Click + New Message.',
                'Select recipients, type message, send.',
            ],
            'tips' => [
                'Use messages instead of WhatsApp for farm communication.',
            ],
        ],
    ],
    'Settings' => [
        [
            'id' => 'calendar', 'pages' => ['hub_settings.php'], 'icon' => 'calendar',
            'title' => 'Calendar',
            'summary' => 'Visual calendar showing all events in one view.',
            'steps' => [
                'Open the Calendar tab.',
                'Events from all modules appear here.',
                'Add events directly from the calendar.',
            ],
            'tips' => [
                'Great for weekly planning sessions.',
            ],
        ],
        [
            'id' => 'dropdowns', 'pages' => ['hub_settings.php'], 'icon' => 'list',
            'title' => 'Dropdowns',
            'summary' => 'Customize dropdown options used across the system.',
            'steps' => [
                'Open the Dropdowns tab.',
                'Select the category to edit.',
                'Add, edit, or remove options.',
            ],
            'tips' => [
                'Add your specific breeds and species for faster data entry.',
            ],
        ],
        [
            'id' => 'app-settings', 'pages' => ['hub_settings.php'], 'icon' => 'settings',
            'title' => 'App Settings',
            'summary' => 'Configure farm profile, currency, units, and subscription.',
            'steps' => [
                'Update Farm Details: name, location, phone.',
                'Set Preferences: currency, date format, units.',
                'Manage Subscription: view plan, upgrade.',
            ],
            'tips' => [
                'Set your farm location for weather features.',
            ],
        ],
        [
            'id' => 'logs', 'pages' => ['hub_settings.php'], 'icon' => 'history',
            'title' => 'Activity Logs',
            'summary' => 'Complete audit trail of everything in the system.',
            'steps' => [
                'Open Activity Logs tab.',
                'Every action is recorded with user and timestamp.',
                'Filter by user or date range.',
            ],
            'tips' => [
                'Logs are append-only and cannot be edited.',
            ],
        ],
        [
            'id' => 'roles', 'pages' => ['hub_settings.php'], 'icon' => 'shield',
            'title' => 'Roles and Permissions',
            'summary' => 'Control which modules each role can open.',
            'steps' => [
                'Open the Roles tab.',
                'Click a role card to expand the module matrix.',
                'Tick View or Edit for each module.',
                'Click Save.',
            ],
            'tips' => [
                'Farm Manager always has full access.',
                'Workers only see enabled modules.',
            ],
        ],
    ],
];

/* ── Build a script-name → guide-id map for auto-detection ── */
$guidePageMap = [];
foreach ($guideSections as $_group) {
    foreach ($_group as $_mod) {
        foreach ($_mod['pages'] as $_pg) {
            $guidePageMap[$_pg] = $_mod['id'];
        }
    }
}
$guideOrder = [];
foreach ($guideSections as $_group) {
    foreach ($_group as $_mod) {
        $guideOrder[] = $_mod['id'];
    }
}
?>
<!-- Premium Interactive System Walkthrough Guide Modal -->
<div id="system-guide-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
    <div style="background: #ffffff; width: 94%; max-width: 900px; border-radius: 14px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); display: flex; flex-direction: column; overflow: hidden; transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); max-height: 88vh;">

        <!-- Header -->
        <div style="background: linear-gradient(135deg, var(--admin-primary) 0%, #064e3b 100%); padding: 20px 26px; color: #ffffff; display: flex; justify-content: space-between; align-items: center; position: relative; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.3rem; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="help-circle" style="width: 24px; height: 24px;"></i>
                    System Walkthrough Guide
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 0.83rem; color: rgba(255, 255, 255, 0.82);">Every module, every flow — search for a module or pick it from the list.</p>
            </div>
            <button id="close-system-guide" aria-label="Close guide" style="background: rgba(255,255,255,0.15); border: none; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #ffffff; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <!-- Guide Content Body -->
        <div style="display: flex; flex: 1; min-height: 400px; overflow: hidden; background: #f8fafc;">

            <!-- Left: grouped module navigation -->
            <div style="width: 250px; border-right: 1px solid rgba(203, 213, 225, 0.8); background: #ffffff; padding: 12px 10px; display: flex; flex-direction: column; gap: 10px; overflow-y: auto; flex-shrink: 0;">
                <input id="guide-search" type="text" placeholder="Search modules…" style="padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; outline: none; width: 100%; box-sizing: border-box;">
                <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                    <?php $gi = 0; foreach ($guideSections as $gLabel => $mods): ?>
                    <div class="guide-group">
                        <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; padding: 2px 8px 4px;"><?= htmlspecialchars($gLabel, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($mods as $mod): ?>
                        <button class="guide-nav-btn<?= $gi === 0 ? ' active' : '' ?>" onclick="showGuide('<?= $mod['id'] ?>')" data-guide-id="<?= $mod['id'] ?>" style="display: flex; align-items: center; gap: 9px; width: 100%; padding: 8px 10px; border: none; background: none; border-radius: 7px; text-align: left; font-weight: 600; font-size: 0.83rem; color: #475569; cursor: pointer; transition: all 0.15s;">
                            <i data-lucide="<?= $mod['icon'] ?>" style="width: 15px; height: 15px; flex-shrink: 0;"></i>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($mod['title'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <?php $gi++; endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: module detail -->
            <div id="guide-panes" style="flex: 1; padding: 26px 28px; overflow-y: auto; min-width: 0;">
                <?php $pi = 0; foreach ($guideSections as $_group): foreach ($_group as $mod): ?>
                <div class="guide-step-pane" data-pane-id="<?= $mod['id'] ?>" style="display: <?= $pi === 0 ? 'block' : 'none' ?>;">
                    <h4 style="margin: 0 0 8px 0; font-size: 1.2rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 9px;">
                        <span style="width: 34px; height: 34px; border-radius: 9px; background: rgba(27,94,32,0.08); color: var(--admin-primary); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;"><i data-lucide="<?= $mod['icon'] ?>" style="width: 18px; height: 18px;"></i></span>
                        <?= htmlspecialchars($mod['title'], ENT_QUOTES, 'UTF-8') ?>
                    </h4>
                    <p style="margin: 0 0 16px 0; line-height: 1.6; font-size: 0.92rem; color: #475569;"><?= $mod['summary'] ?></p>

                    <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--admin-primary); margin-bottom: 8px;">How it works</div>
                    <ol style="margin: 0 0 18px 0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: #334155; line-height: 1.55;">
                        <?php foreach ($mod['steps'] as $step): ?>
                        <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>

                    <?php if (!empty($mod['tips'])): ?>
                    <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #d97706; margin-bottom: 8px;">Good to know</div>
                    <ul style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 7px; font-size: 0.88rem; color: #78350f; line-height: 1.5; background: #fffbeb; border: 1px solid #fde68a; border-radius: 9px; padding: 14px 14px 14px 30px;">
                        <?php foreach ($mod['tips'] as $tip): ?>
                        <li><?= $tip ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php $pi++; endforeach; endforeach; ?>
            </div>
        </div>

        <!-- Footer / Action Controls -->
        <div style="background: #ffffff; border-top: 1px solid rgba(203, 213, 225, 0.8); padding: 14px 26px; display: flex; justify-content: space-between; align-items: center;">
            <button id="guide-prev" onclick="guideMove(-1)" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 6px;">
                <i data-lucide="chevron-left" style="width: 16px; height: 16px;"></i> Prev
            </button>
            <div style="font-size: 0.82rem; color: #64748b; font-weight: 600;"><span id="guide-count">1</span> of <?= count($guideOrder) ?></div>
            <button id="guide-next" onclick="guideMove(1)" class="btn btn-primary btn-sm" style="display: flex; align-items: center; gap: 6px;">
                Next <i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>
            </button>
        </div>

    </div>
</div>

<style>
.guide-nav-btn:hover { color: var(--admin-primary) !important; background: rgba(27, 94, 32, 0.05) !important; }
.guide-nav-btn.active { color: #ffffff !important; background: var(--admin-primary) !important; }
@media (max-width: 700px) {
    #system-guide-modal > div { flex-direction: column; }
    #system-guide-modal .guide-group + div { display: none; }
}
</style>

<script>
/* Ordered ids for prev/next + auto-detection */
const guideOrder = <?= json_encode($guideOrder) ?>;
const guidePageMap = <?= json_encode($guidePageMap) ?>;
let currentGuide = guideOrder[0] || null;

function showGuide(id, opts) {
    if (!guideOrder.includes(id)) id = guideOrder[0];
    currentGuide = id;
    document.querySelectorAll('.guide-step-pane').forEach(p => {
        p.style.display = p.getAttribute('data-pane-id') === id ? 'block' : 'none';
    });
    document.querySelectorAll('.guide-nav-btn').forEach(b => {
        b.classList.toggle('active', b.getAttribute('data-guide-id') === id);
    });
    const idx = guideOrder.indexOf(id) + 1;
    document.getElementById('guide-count').textContent = idx;
    document.getElementById('guide-prev').disabled = idx === 1;
    document.getElementById('guide-next').innerHTML = idx === guideOrder.length
        ? 'Finish <i data-lucide="check" style="width: 16px; height: 16px;"></i>'
        : 'Next <i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>';
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Scroll the active nav item into view inside the left column
    const btn = document.querySelector('.guide-nav-btn.active');
    if (btn) btn.scrollIntoView({ block: 'nearest' });
}

function guideMove(delta) {
    const idx = guideOrder.indexOf(currentGuide) + delta;
    if (idx >= guideOrder.length) { closeGuideModal(); return; }
    showGuide(guideOrder[Math.max(0, idx)]);
}

function openGuideModal() {
    const modal = document.getElementById('system-guide-modal');
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.firstElementChild.style.transform = 'translateY(0)';
    }, 10);
    // Auto-detect the current page and open its module.
    const path = window.location.pathname;
    const page = path.split('/').pop() || '';
    showGuide(guidePageMap[page] || 'dashboard');
    // Reset search each time
    const search = document.getElementById('guide-search');
    if (search) { search.value = ''; filterGuide(''); }
}

function filterGuide(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.guide-nav-btn').forEach(b => {
        const text = (b.textContent || '').toLowerCase();
        b.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
    document.querySelectorAll('.guide-group').forEach(g => {
        const visible = [...g.querySelectorAll('.guide-nav-btn')].some(b => b.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}

function closeGuideModal() {
    const modal = document.getElementById('system-guide-modal');
    modal.style.opacity = '0';
    if (modal.firstElementChild) modal.firstElementChild.style.transform = 'translateY(20px)';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.getElementById('open-system-guide');
    if (trigger) trigger.addEventListener('click', openGuideModal);

    const closeBtn = document.getElementById('close-system-guide');
    if (closeBtn) closeBtn.addEventListener('click', closeGuideModal);

    const search = document.getElementById('guide-search');
    if (search) search.addEventListener('input', () => filterGuide(search.value));

    // Never auto-open: the guide stays available via the help button only.
    const modal = document.getElementById('system-guide-modal');
    if (modal) {
        modal.addEventListener('click', (e) => { if (e.target === modal) closeGuideModal(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display !== 'none') closeGuideModal();
        });
    }
});
</script>
