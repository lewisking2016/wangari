<?php
/**
 * Wangari Onboarding Wizard — Goal Picker
 * 
 * After registration, farmers pick ONE primary goal.
 * The system shows ONLY that module. Others unlock gradually.
 * 
 * This is the "Trojan Horse" strategy from Document 1:
 * Don't show 12 modules. Show 1. Let them fall in love.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    wangariStartSession();
}

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$page_title = 'Set Up Your Farm — Wangari';

// Handle goal selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['goal'])) {
    $goal = trim($_POST['goal']);
    $valid_goals = ['poultry', 'livestock', 'crops', 'inventory', 'finance', 'sales', 'reports'];
    
    if (in_array($goal, $valid_goals)) {
        $pdo = getDB();
        if ($pdo) {
            // Store the selected goal
            $stmt = $pdo->prepare("UPDATE users SET primary_goal = ? WHERE id = ?");
            $stmt->execute([$goal, $user_id]);
            
            // Also store in session for immediate use
            $_SESSION['primary_goal'] = $goal;
            
            // Redirect to admin dashboard (the main working dashboard)
            header('Location: /Frontend/admin/dashboard.php?goal=' . urlencode($goal));
            exit;
        }
    }
}
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0a0f0d;
            color: #e5e7eb;
            font-family: 'Inter Tight', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .onboarding-container {
            max-width: 900px;
            width: 100%;
        }
        .onboarding-header {
            text-align: center;
            margin-bottom: 48px;
        }
        .onboarding-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 100px;
            padding: 6px 16px;
            margin-bottom: 20px;
            color: #22C55E;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .onboarding-title {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 12px;
        }
        .onboarding-title span {
            color: #22C55E;
            font-family: 'Instrument Serif', serif;
            font-style: italic;
        }
        .onboarding-subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 1.05rem;
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto;
        }
        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .goal-card {
            background: rgba(255,255,255,0.03);
            border: 2px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 28px 24px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .goal-card:hover {
            border-color: rgba(34,197,94,0.4);
            background: rgba(34,197,94,0.05);
            transform: translateY(-2px);
        }
        .goal-card.selected {
            border-color: #22C55E;
            background: rgba(34,197,94,0.1);
        }
        .goal-card.selected::after {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 12px;
            width: 24px;
            height: 24px;
            background: #22C55E;
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .goal-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .goal-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .goal-card p {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }
        .continue-btn {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 16px;
            background: #22C55E;
            color: #000;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
        }
        .continue-btn:hover {
            background: #16A34A;
            transform: translateY(-1px);
        }
        .continue-btn:disabled {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.3);
            cursor: not-allowed;
            transform: none;
        }
        .skip-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: rgba(255,255,255,0.4);
            font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .skip-link:hover {
            color: rgba(255,255,255,0.7);
        }
        @media (max-width: 640px) {
            .onboarding-title { font-size: 1.6rem; }
            .goals-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <div class="onboarding-header">
            <div class="onboarding-eyebrow">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Step 1 of 2
            </div>
            <h1 class="onboarding-title">What's the <span>#1 thing</span><br>you want to track?</h1>
            <p class="onboarding-subtitle">Pick one. You can always add more later. We'll show you exactly what you need, nothing confusing, nothing extra.</p>
        </div>

        <form method="POST" id="goalForm">
            <div class="goals-grid">
                <!-- Poultry -->
                <label class="goal-card" onclick="selectGoal(this, 'poultry')">
                    <input type="radio" name="goal" value="poultry" style="display:none">
                    <div class="goal-icon" style="background: linear-gradient(135deg, #22C55E22, #16A34A11);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h3>🐔 My Poultry</h3>
                    <p>Track eggs, mortality, feed, FCR, vaccination schedules, and flock performance.</p>
                </label>

                <!-- Livestock -->
                <label class="goal-card" onclick="selectGoal(this, 'livestock')">
                    <input type="radio" name="goal" value="livestock" style="display:none">
                    <div class="goal-icon" style="background: linear-gradient(135deg, #F59E0B22, #D9770611);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M20 7h-3a2 2 0 0 0-2 2v.5M4 7h3a2 2 0 0 1 2 2v.5M12 4v3M9.5 9.5C8 11 7 13 7 15c0 2.8 2.2 5 5 5s5-2.2 5-5c0-2-1-4-2.5-5.5"/></svg>
                    </div>
                    <h3>🐄 My Livestock</h3>
                    <p>Track individual animals, milk production, breeding cycles, treatments, and vet visits.</p>
                </label>

                <!-- Crops -->
                <label class="goal-card" onclick="selectGoal(this, 'crops')">
                    <input type="radio" name="goal" value="crops" style="display:none">
                    <div class="goal-icon" style="background: linear-gradient(135deg, #10B98122, #05966911);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M12 22V12M12 12C12 7 7 3 2 3c0 5 4 9 10 9z"/><path d="M12 12c0-5 5-9 10-9-1 5-5 9-10 9"/></svg>
                    </div>
                    <h3>🌾 My Crops</h3>
                    <p>Map fields, track planting seasons, input costs, harvest yields, and profit per acre.</p>
                </label>

                <!-- Inventory -->
                <label class="goal-card" onclick="selectGoal(this, 'inventory')">
                    <input type="radio" name="goal" value="inventory" style="display:none">
                    <div class="goal-icon" style="background: linear-gradient(135deg, #EF444422, #DC262611);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <h3>📦 My Inventory</h3>
                    <p>Track feed stock, medicines, supplies. Get low-stock alerts before you run out.</p>
                </label>

                <!-- Finance -->
                <label class="goal-card" onclick="selectGoal(this, 'finance')">
                    <input type="radio" name="goal" value="finance" style="display:none">
                    <div class="goal-icon" style="background: linear-gradient(135deg, #6366F122, #4F46E511);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3>💰 My Money</h3>
                    <p>Track income, expenses, M-Pesa payments, and see your real profit, calculated automatically.</p>
                </label>

                <!-- Sales -->
                <label class="goal-card" onclick="selectGoal(this, 'sales')">
                    <input type="radio" name="goal" value="sales" style="display:none">
                    <div class="goal-icon" style="background: linear-gradient(135deg, #EC489922, #DB277711);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>🤝 My Customers</h3>
                    <p>Track orders, invoices, who owes you money, and manage customer credit.</p>
                </label>
            </div>

            <button type="submit" class="continue-btn" id="continueBtn" disabled>
                Continue with Poultry →
            </button>
        </form>

        <a href="/Frontend/admin/dashboard.php" class="skip-link">Skip for now, I'll explore on my own</a>
    </div>

    <script>
    function selectGoal(card, goal) {
        // Remove selected from all cards
        document.querySelectorAll('.goal-card').forEach(c => c.classList.remove('selected'));
        // Add selected to clicked card
        card.classList.add('selected');
        
        // Update button text
        const btn = document.getElementById('continueBtn');
        const labels = {
            'poultry': 'Poultry',
            'livestock': 'Livestock', 
            'crops': 'Crops',
            'inventory': 'Inventory',
            'finance': 'Finance',
            'sales': 'Customers'
        };
        btn.textContent = 'Continue with ' + labels[goal] + ' →';
        btn.disabled = false;
    }
    </script>
</body>
</html>
