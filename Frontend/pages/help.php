<?php
/**
 * Wangari Help Center
 * 
 * Quick reference for:
 * - WhatsApp bot commands
 * - Dashboard navigation
 * - Agent instructions
 * - FAQ
 */
declare(strict_types=1);

$page_title = 'Help Center — Wangari';
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
        body { background: #0a0f0d; color: #e5e7eb; font-family: 'Inter Tight', sans-serif; padding: 24px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { font-size: 2rem; font-weight: 800; margin-bottom: 8px; }
        h2 { font-size: 1.3rem; font-weight: 700; margin: 32px 0 16px; color: #22C55E; }
        h3 { font-size: 1rem; font-weight: 600; margin: 16px 0 8px; }
        .subtitle { color: rgba(255,255,255,0.5); margin-bottom: 32px; }
        
        .section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        
        .cmd-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .cmd-table th { text-align: left; padding: 8px 12px; color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .cmd-table td { padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        .cmd-table code { background: rgba(34,197,94,0.1); color: #22C55E; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; }
        
        .tip { background: rgba(34,197,94,0.06); border-left: 3px solid #22C55E; padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 12px 0; font-size: 0.9rem; }
        .warning { background: rgba(245,158,11,0.06); border-left: 3px solid #F59E0B; padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 12px 0; font-size: 0.9rem; }
        
        a { color: #22C55E; text-decoration: none; }
        a:hover { text-decoration: underline; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>📖 Wangari Help Center</h1>
        <p class="subtitle">Everything you need to know about using Wangari.</p>
        
        <!-- Quick Links -->
        <div class="grid-2">
            <a href="#whatsapp" class="section" style="text-decoration:none; color:inherit; cursor:pointer;">
                <h3>📱 WhatsApp Bot</h3>
                <p style="color:rgba(255,255,255,0.5); font-size:0.85rem;">Text your farm data via WhatsApp</p>
            </a>
            <a href="#dashboard" class="section" style="text-decoration:none; color:inherit; cursor:pointer;">
                <h3>💻 Dashboard</h3>
                <p style="color:rgba(255,255,255,0.5); font-size:0.85rem;">Manage your farm on the web</p>
            </a>
            <a href="#ussd" class="section" style="text-decoration:none; color:inherit; cursor:pointer;">
                <h3>📞 USSD (Basic Phones)</h3>
                <p style="color:rgba(255,255,255,0.5); font-size:0.85rem;">No smartphone needed</p>
            </a>
            <a href="#agent" class="section" style="text-decoration:none; color:inherit; cursor:pointer;">
                <h3>🧑‍🌾 Agent Guide</h3>
                <p style="color:rgba(255,255,255,0.5); font-size:0.85rem;">For field agents onboarding farmers</p>
            </a>
        </div>
        
        <!-- WhatsApp Bot -->
        <div class="section" id="whatsapp">
            <h2>📱 WhatsApp Bot Commands</h2>
            <p>Send these messages to the Wangari WhatsApp number to enter data and get reports.</p>
            
            <h3>Data Entry</h3>
            <table class="cmd-table">
                <thead><tr><th>Command</th><th>What It Does</th><th>Example</th></tr></thead>
                <tbody>
                    <tr><td><code>eggs 40</code></td><td>Log egg collection</td><td>"eggs 40" → 40 eggs logged</td></tr>
                    <tr><td><code>mortality 2</code></td><td>Log bird deaths</td><td>"mortality 2" → 2 deaths logged</td></tr>
                    <tr><td><code>feed 3 bags</code></td><td>Log feed usage + cost</td><td>"feed 3 bags" → KES 1,500 cost</td></tr>
                    <tr><td><code>milk 15</code></td><td>Log milk yield (litres)</td><td>"milk 15" → 15L logged</td></tr>
                    <tr><td><code>sold 10 crates @ 400</code></td><td>Log a sale</td><td>"sold 10 crates @ 400" → KES 4,000</td></tr>
                    <tr><td><code>buy feed 20 bags @ 500</code></td><td>Log purchase + inventory</td><td>"buy feed 20 bags @ 500" → KES 10,000</td></tr>
                    <tr><td><code>expense 2000 transport</code></td><td>Log miscellaneous expense</td><td>"expense 2000 transport"</td></tr>
                </tbody>
            </table>
            
            <h3>View Reports</h3>
            <table class="cmd-table">
                <thead><tr><th>Command</th><th>What You See</th></tr></thead>
                <tbody>
                    <tr><td><code>summary</code></td><td>Today's full report (eggs, mortality, costs, profit)</td></tr>
                    <tr><td><code>week</code></td><td>This week's totals</td></tr>
                    <tr><td><code>month</code></td><td>This month's totals with comparison to last month</td></tr>
                    <tr><td><code>stock</code></td><td>Current inventory levels with low-stock alerts</td></tr>
                    <tr><td><code>profit</code></td><td>Running profit/loss with margin percentage</td></tr>
                    <tr><td><code>fcr</code></td><td>Feed conversion ratio vs Kenya benchmark</td></tr>
                    <tr><td><code>mortality</code></td><td>Mortality report with health assessment</td></tr>
                    <tr><td><code>credit</code></td><td>Outstanding customer debts</td></tr>
                </tbody>
            </table>
            
            <h3>Market Prices</h3>
            <table class="cmd-table">
                <thead><tr><th>Command</th><th>What You See</th></tr></thead>
                <tbody>
                    <tr><td><code>price eggs</code></td><td>Current egg prices (wholesale + retail)</td></tr>
                    <tr><td><code>price feed</code></td><td>Current feed prices (layers, broiler, starter)</td></tr>
                    <tr><td><code>price chicken</code></td><td>Poultry prices (live + dressed)</td></tr>
                    <tr><td><code>price milk</code></td><td>Dairy milk prices (farm gate + retail)</td></tr>
                    <tr><td><code>price</code></td><td>All market prices at once</td></tr>
                </tbody>
            </table>
            
            <h3>AI Questions</h3>
            <table class="cmd-table">
                <thead><tr><th>Command</th><th>What Wangari Answers</th></tr></thead>
                <tbody>
                    <tr><td><code>why low eggs?</code></td><td>Analyzes your data + gives causes + solutions</td></tr>
                    <tr><td><code>why high mortality?</code></td><td>Health check with action steps</td></tr>
                    <tr><td><code>when vaccinate?</code></td><td>Upcoming vaccination schedule</td></tr>
                </tbody>
            </table>
            
            <div class="tip">
                💡 <strong>Tip:</strong> Send "help" anytime to see all commands. Your data syncs to your Wangari dashboard automatically.
            </div>
        </div>
        
        <!-- USSD -->
        <div class="section" id="ussd">
            <h2>📞 USSD (Basic Phones)</h2>
            <p>Don't have a smartphone? Use USSD on any phone.</p>
            
            <h3>How to Access</h3>
            <p>Dial <code>*123#</code> (or the configured short code) on your phone.</p>
            
            <h3>Menu Options</h3>
            <table class="cmd-table">
                <thead><tr><th>Option</th><th>What It Does</th></tr></thead>
                <tbody>
                    <tr><td>1. Enter Production</td><td>Enter eggs, mortality, milk, feed, sales</td></tr>
                    <tr><td>2. View Summary</td><td>Today's, weekly, or monthly totals</td></tr>
                    <tr><td>3. View Stock</td><td>Current inventory levels</td></tr>
                    <tr><td>4. Get Advice</td><td>Vaccination reminders, market prices, farming tips</td></tr>
                    <tr><td>5. My Account</td><td>Your account info + support number</td></tr>
                </tbody>
            </table>
            
            <div class="warning">
                ⚠️ <strong>Note:</strong> USSD sessions timeout after 3 minutes. Enter data quickly or save between screens.
            </div>
        </div>
        
        <!-- Dashboard -->
        <div class="section" id="dashboard">
            <h2>💻 Dashboard Guide</h2>
            <p>Access your full farm management system at <a href="/Frontend/admin/dashboard.php">wangari.imeantech.com/dashboard</a></p>
            
            <h3>Available Modules</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin: 12px 0;">
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #22C55E;">🐔 Poultry</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Flocks, daily records, FCR, vaccinations</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #F59E0B;">🐄 Livestock</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Individual animals, milk, breeding</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #10B981;">🌾 Crops</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Fields, planting, harvest, cost-per-kg</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #EF4444;">📦 Feed Mill</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Formulas, batch production, cost-per-bag</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #6366F1;">💰 Finance</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Cashbook, P&L, M-Pesa reconciliation</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #EC4899;">🤝 CRM</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Orders, invoices, credit tracking</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #0EA5E9;">📊 Reports</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">KPIs, trends, export to PDF/CSV</p>
                </div>
                <div style="padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <strong style="color: #A855F7;">🤖 AI Assistant</strong>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Ask questions in plain language</p>
                </div>
            </div>
        </div>
        
        <!-- Agent Guide -->
        <div class="section" id="agent">
            <h2>🧑‍🌾 Agent Guide</h2>
            <p>For field agents onboarding farmers.</p>
            
            <h3>Your Daily Routine</h3>
            <ol style="padding-left: 20px; margin: 12px 0;">
                <li style="margin-bottom: 8px;"><strong>8:00 AM</strong> — Check Agent Dashboard (which farmers are inactive?)</li>
                <li style="margin-bottom: 8px;"><strong>9:00 AM - 5:00 PM</strong> — Visit 5 farms per day</li>
                <li style="margin-bottom: 8px;"><strong>At each farm:</strong> Enter data for the farmer, check their progress, answer questions</li>
                <li style="margin-bottom: 8px;"><strong>6:00 PM</strong> — Update Agent Dashboard, submit daily report</li>
            </ol>
            
            <h3>Creating an Account for a Farmer</h3>
            <ol style="padding-left: 20px; margin: 12px 0;">
                <li style="margin-bottom: 8px;">Go to <a href="/Frontend/admin/agent_dashboard.php">Agent Dashboard</a></li>
                <li style="margin-bottom: 8px;">Click "Create Account"</li>
                <li style="margin-bottom: 8px;">Enter farmer's name, email, phone, farm name</li>
                <li style="margin-bottom: 8px;">System creates account with password: <code>wangari123</code></li>
                <li style="margin-bottom: 8px;">Tell the farmer their email and password</li>
                <li style="margin-bottom: 8px;">Help them set up their first module (use Goal Picker)</li>
            </ol>
            
            <h3>Entering Data for a Farmer</h3>
            <ol style="padding-left: 20px; margin: 12px 0;">
                <li style="margin-bottom: 8px;">Go to Agent Dashboard → Quick Entry tab</li>
                <li style="margin-bottom: 8px;">Select the farmer from the dropdown</li>
                <li style="margin-bottom: 8px;">Enter today's eggs, mortality, feed bags</li>
                <li style="margin-bottom: 8px;">Click "Save Entry"</li>
            </ol>
            
            <div class="tip">
                💡 <strong>Pro tip:</strong> Show the farmer the WhatsApp bot. Say: "After I leave, just text me your numbers like this: eggs 40, mortality 2, feed 3 bags. It's easier than waiting for me to visit."
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="section">
            <h2>❓ Frequently Asked Questions</h2>
            
            <h3>Q: Is Wangari free?</h3>
            <p>A: Yes! Start with a 30-day free trial. After that, plans start at KES 500/month.</p>
            
            <h3>Q: Do I need internet?</h3>
            <p>A: For the web dashboard, yes. But the WhatsApp bot works on any phone, and USSD works without internet. Offline mode is coming soon.</p>
            
            <h3>Q: Is my data safe?</h3>
            <p>A: Yes. Your data is encrypted, stored securely, and you own it 100%. You can export or delete everything at any time.</p>
            
            <h3>Q: Can I use it for dairy/crops too?</h3>
            <p>A: Yes! Wangari supports poultry, livestock/dairy, crops, feed mills, and mixed farms.</p>
            
            <h3>Q: How do I contact support?</h3>
            <p>A: WhatsApp us at the same number you use for the bot, or email support@wangari.imeantech.com</p>
            
            <h3>Q: Can I share my account with my farm manager?</h3>
            <p>A: Yes! Add team members with different roles (Admin, Manager, Worker) from the Team settings.</p>
        </div>
        
        <!-- Contact -->
        <div class="section" style="text-align: center;">
            <h2>📞 Still Need Help?</h2>
            <p>WhatsApp us or email support@wangari.imeantech.com</p>
            <p style="margin-top: 12px;">
                <a href="https://wa.me/254700000000" style="display: inline-block; padding: 12px 24px; background: #25D366; color: #fff; border-radius: 8px; font-weight: 600; margin-top: 8px;">💬 WhatsApp Support</a>
            </p>
        </div>
    </div>
</body>
</html>
