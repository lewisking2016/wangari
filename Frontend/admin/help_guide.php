<?php
/**
 * Help & User Guide — Wangari Farm OS
 * Designed in-app documentation for farmers.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/database.php';
require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDatabaseConnection();
$userName = htmlspecialchars($_SESSION['username'] ?? 'User');
$page_title = 'Help & User Guide | Wangari';

// Handle profile update
$profileMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newFullName = trim($_POST['new_full_name'] ?? '');
    $userId = (int)$_SESSION['user_id'];
    
    if ($newUsername !== '' && strlen($newUsername) >= 3) {
        // Check if username is taken
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$newUsername, $userId]);
        if (!$stmt->fetch()) {
            $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$newUsername, $userId]);
            $_SESSION['username'] = $newUsername;
            $userName = htmlspecialchars($newUsername);
            $profileMsg = '<div style="background:#DCFCE7;border:1px solid #86EFAC;border-radius:10px;padding:12px 16px;color:#166534;font-weight:600;font-size:0.88rem;margin-bottom:20px;">Profile updated successfully!</div>
                </div>
            </form>
        </div>

        <!-- Quick Navigation -->
        <div class="help-nav">
            <a href="#getting-started" class="help-nav-item"><div class="h-icon" style="background:#F0FDF4;color:#166534;"><i data-lucide="rocket" style="width:18px;height:18px;"></i></div>Getting Started</a>
            <a href="#dashboard" class="help-nav-item"><div class="h-icon" style="background:#EFF6FF;color:#1D4ED8;"><i data-lucide="layout-dashboard" style="width:18px;height:18px;"></i></div>Dashboard</a>
            <a href="#farm-operations" class="help-nav-item"><div class="h-icon" style="background:#F0FDF4;color:#166534;"><i data-lucide="sprout" style="width:18px;height:18px;"></i></div>Farm Operations</a>
            <a href="#sales" class="help-nav-item"><div class="h-icon" style="background:#FEF5E0;color:#B45309;"><i data-lucide="trending-up" style="width:18px;height:18px;"></i></div>Sales & Finance</a>
            <a href="#customers" class="help-nav-item"><div class="h-icon" style="background:#FDF2F8;color:#BE185D;"><i data-lucide="users" style="width:18px;height:18px;"></i></div>Customers</a>
            <a href="#workers" class="help-nav-item"><div class="h-icon" style="background:#FEF5E0;color:#B45309;"><i data-lucide="hard-hat" style="width:18px;height:18px;"></i></div>Workers</a>
            <a href="#ai-assistant" class="help-nav-item"><div class="h-icon" style="background:#F5F3FF;color:#7C3AED;"><i data-lucide="sparkles" style="width:18px;height:18px;"></i></div>AI Assistant</a>
            <a href="#subscription" class="help-nav-item"><div class="h-icon" style="background:#FFF7ED;color:#C2410C;"><i data-lucide="credit-card" style="width:18px;height:18px;"></i></div>Subscription</a>
        </div>

<div id="getting-started" class="help-section">
<h2><span class="h-num">1</span> Getting Started</h2>
<p>Wangari is your all-in-one farm management system. It runs in your web browser - no download needed.</p>
<h3>First Time Setup</h3>
<div class="help-step"><div class="help-step-num">1</div><p>Log in with your email/username and password</p></div>
<div class="help-step"><div class="help-step-num">2</div><p>The Onboarding Wizard will ask for your farm name, location, and type</p></div>
<div class="help-step"><div class="help-step-num">3</div><p>Add your first animals (cows, chickens, goats, etc.)</p></div>
<div class="help-step"><div class="help-step-num">4</div><p>You are ready! Explore the Dashboard and sidebar menu</p></div>
<div class="help-tip"><div class="help-tip-icon"><i data-lucide="lightbulb" style="width:18px;height:18px;"></i></div><p>You get a <strong>30-day free trial</strong> with full access. No card required.</p></div>
</div>
<div id="dashboard" class="help-section">
<h2><span class="h-num">2</span> Dashboard</h2>
<p>Your home screen shows everything at a glance:</p>
<ul><li><strong>KPI Cards</strong> - Revenue, Orders, Animals, Crops, Workers, Customers (tap to navigate)</li>
<li><strong>Revenue Chart</strong> - 7-day sales trend</li>
<li><strong>Weather Widget</strong> - Live weather for your farm</li>
<li><strong>Financial Summary</strong> - Income vs Expenses vs Profit</li></ul>
<div class="help-tip"><div class="help-tip-icon"><i data-lucide="lightbulb" style="width:18px;height:18px;"></i></div><p>Use the <strong>sidebar menu</strong> to navigate. On mobile, tap the hamburger icon.</p></div>
</div>
<div id="farm-operations" class="help-section">
<h2><span class="h-num">3</span> Farm Operations</h2>
<p>The core module for managing livestock with 10 sub-tabs:</p>
<h3>Animals</h3><p>Track every animal. Click + Add Animal, fill in tag, name, species, breed, gender, DOB.</p>
<h3>Groups</h3><p>Manage flocks/herds together (e.g., Layer Flock A with 500 chickens).</p>
<h3>Health Records</h3><p>Log sickness, treatments, vet visits. Spot problems early.</p>
<h3>Vaccinations</h3><p>Schedule vaccines with built-in guides for cattle, poultry, and goats.</p>
<h3>Production</h3><p>Daily log: eggs, milk, weight, feed used, mortality per group.</p>
<h3>Poultry Tools</h3><p>FCR Calculator, HDP Calculator, Batch Profitability.</p>
</div>
<div id="sales" class="help-section">
<h2><span class="h-num">4</span> Sales & Finance</h2>
<h3>Creating an Order</h3>
<div class="help-step"><div class="help-step-num">1</div><p>Go to Sales & Finance > Customer Orders</p></div>
<div class="help-step"><div class="help-step-num">2</div><p>Click + New Order, select customer</p></div>
<div class="help-step"><div class="help-step-num">3</div><p>Add items (products, quantities, prices)</p></div>
<div class="help-step"><div class="help-step-num">4</div><p>Click Save Order</p></div>
<h3>Recording Payments</h3><p>Incoming Payments > + Log Payment. Record who paid, amount, method.</p>
<h3>Expenses</h3><p>Track feed, medication, labour, transport, and all spending.</p>
</div>
<div id="customers" class="help-section">
<h2><span class="h-num">5</span> CRM & Customers</h2>
<h3>Add a Customer</h3>
<div class="help-step"><div class="help-step-num">1</div><p>CRM > All Customers > + Add Customer</p></div>
<div class="help-step"><div class="help-step-num">2</div><p>Fill in name, phone, type (Wholesale/Retail/Restaurant)</p></div>
<div class="help-step"><div class="help-step-num">3</div><p>Click Save</p></div>
<h3>Follow-ups</h3><p>Schedule reminders to contact customers. Never miss a follow-up.</p>
</div>
<div id="workers" class="help-section">
<h2><span class="h-num">6</span> Labour & Workers</h2>
<h3>Add a Worker</h3>
<div class="help-step"><div class="help-step-num">1</div><p>Labour > Workers > + Add Worker</p></div>
<div class="help-step"><div class="help-step-num">2</div><p>Fill in name, phone, role, daily wage</p></div>
<div class="help-step"><div class="help-step-num">3</div><p>Click Save</p></div>
<h3>Attendance</h3><p>Record clock-in/out times daily.</p>
<h3>Wage Payments</h3><p>Record payments with amount, period, method (Cash/M-Pesa).</p>
</div>
<div id="ai-assistant" class="help-section">
<h2><span class="h-num">7</span> AI Assistant (Wangari AI)</h2>
<p>Your personal farm AI that reads your records and gives smart answers.</p>
<ul><li>How much did I sell this month?</li><li>Who owes me credit?</li>
<li>What is my profit so far?</li><li>Vaccination schedule for cattle?</li>
<li>What is 30 * 1600?</li></ul>
<div class="help-tip"><div class="help-tip-icon"><i data-lucide="lightbulb" style="width:18px;height:18px;"></i></div><p><strong>40 queries/day</strong> on free trial and Pro. Plus gets unlimited.</p></div>
</div>
<div id="subscription" class="help-section">
<h2><span class="h-num">8</span> Subscription & Payment</h2>
<ul><li><strong>Pro (KES 1,500/mo)</strong> - 3 modules, 5 cows / 100 birds</li>
<li><strong>Plus (KES 4,500/mo)</strong> - All modules, 200 cows / 2,000 birds</li>
<li><strong>Custom (KES 12,000+/mo)</strong> - Everything unlimited</li></ul>
<h3>How to Subscribe</h3>
<div class="help-step"><div class="help-step-num">1</div><p>Go to Pricing page</p></div>
<div class="help-step"><div class="help-step-num">2</div><p>Click Subscribe, choose plan</p></div>
<div class="help-step"><div class="help-step-num">3</div><p>Pay with M-Pesa, Card, or Bank via Paystack</p></div>
<div class="help-step"><div class="help-step-num">4</div><p>Plan activates immediately</p></div>
<div class="help-tip"><div class="help-tip-icon"><i data-lucide="lightbulb" style="width:18px;height:18px;"></i></div><p>30-day free trial gives full access. Upgrade anytime.</p></div>
</div>
<div style="text-align:center;padding:20px 0 40px;">
<p style="color:#94A3B8;font-size:0.85rem;margin-bottom:8px;">Need more help?</p>
<div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
<a href="/Frontend/pages/support.html" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#166534;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.88rem;">Contact Support</a>
<a href="mailto:support@imeantech.com" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#F8FAFC;color:#334155;border:1px solid #E7EAF0;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.88rem;">Email Us</a>
</div>
<p style="color:#CBD5E1;font-size:0.78rem;margin-top:12px;">support@imeantech.com | +254 114 971 070</p>
</div>
</div></div>
<?php include __DIR__ . "/includes/admin_footer.php"; ?>