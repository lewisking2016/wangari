<?php
/**
 * AI Farm Assistant
 * Chat that answers questions from the farm's own records.
 * Research-backed: the single most-upvoted farmer wish is "an AI copilot
 * that learns your farm, remembers your site, and prompts you daily."
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'AI Farm Assistant - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();

/* ── Ask handler (fetch via POST, answered from farm data) ── */
$answer = ''; $asked = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $question = trim($_POST['question'] ?? '');
    if ($question !== '') {
        $asked = $question;
        $answer = answerFarmQuestion($pdo, $question);
        try {
            $pdo->prepare('INSERT INTO ai_chat_logs (user_id, question, answer, mode) VALUES (?,?,?,"local")')
                ->execute([(int)($_SESSION['user_id'] ?? 0), $question, $answer]);
        } catch (Exception $e) { /* log table missing, ignore */ }
    }
}

/**
 * Answer a natural-language question by querying the farm's own data.
 * Safe, permission-checked, parameterized. Falls back to a helpful default.
 */
function answerFarmQuestion(PDO $pdo, string $q): string {
    $q = strtolower($q);
    try {
        // ── Sales / revenue ──
        if (str_contains($q, 'sales') || str_contains($q, 'revenue') || str_contains($q, 'income') || str_contains($q, 'sold') || str_contains($q, 'sell') || str_contains($q, 'earned') || str_contains($q, 'order')) {
            $row = $pdo->query('SELECT COALESCE(SUM(total_amount),0) AS t, COUNT(*) AS c FROM orders WHERE status IN ("paid","completed")')->fetch();
            $today = $pdo->query('SELECT COALESCE(SUM(total_amount),0) AS t FROM orders WHERE DATE(created_at)=CURDATE() AND status IN ("paid","completed")')->fetch();
            return "From your records: total completed sales are KES " . number_format((float)$row['t'], 0) . " across " . (int)$row['c'] . " orders, and KES " . number_format((float)$today['t'], 0) . " today.";
        }
        // ── Credit / debtors ──
        if (str_contains($q, 'credit') || str_contains($q, 'debt') || str_contains($q, 'owe') || str_contains($q, 'owing')) {
            $rows = $pdo->query('SELECT * FROM customer_credits')->fetchAll();
            $total = 0; $names = [];
            foreach ($rows as $r) {
                $bal = (float)($r['balance'] ?? 0);
                if ($bal > 0) { $total += $bal; $names[] = ($r['customer_name'] ?: 'customer') . ' (KES ' . number_format($bal, 0) . ')'; }
            }
            if ($total <= 0) return "Good news: no outstanding customer credit in your records right now.";
            return "You have KES " . number_format($total, 0) . " outstanding in credit. Top balances: " . implode(', ', array_slice($names, 0, 5)) . ". Consider a follow-up reminder for these customers.";
        }
        // ── Feed / stock ──
        if (str_contains($q, 'feed') || str_contains($q, 'stock') || str_contains($q, 'inventory') || str_contains($q, 'low') || str_contains($q, 'raw material')) {
            $alerts = $pdo->query('SELECT * FROM stock_alerts WHERE status="active" ORDER BY created_at DESC LIMIT 5')->fetchAll();
            if (!empty($alerts)) {
                $lines = array_map(fn($a) => $a['item_name'] ?? $a['product_name'] ?? 'item', $alerts);
                return "You have " . count($alerts) . " active low-stock alerts: " . implode(', ', $lines) . ". Restock soon to avoid stopping production.";
            }
            $count = (int)$pdo->query('SELECT COUNT(*) FROM raw_materials')->fetchColumn();
            return "Stock looks healthy in your records: " . $count . " raw materials tracked, and no active low-stock alerts right now.";
        }
        // ── Production / eggs ──
        if (str_contains($q, 'egg') || str_contains($q, 'production') || str_contains($q, 'produced') || str_contains($q, 'produce')) {
            $today = $pdo->query('SELECT COALESCE(SUM(quantity),0) FROM production_records WHERE DATE(record_date)=CURDATE()')->fetchColumn();
            $month = $pdo->query('SELECT COALESCE(SUM(quantity),0) FROM production_records WHERE YEAR(record_date)=YEAR(CURDATE()) AND MONTH(record_date)=MONTH(CURDATE())')->fetchColumn();
            return "Production from your records: " . number_format((float)$today, 0) . " units captured today, " . number_format((float)$month, 0) . " this month. Daily capture keeps your profit picture accurate.";
        }
        // ── Profit / expenses ──
        if (str_contains($q, 'profit') || str_contains($q, 'expense') || str_contains($q, 'cost') || str_contains($q, 'spend')) {
            $inc = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type="income"')->fetchColumn();
            $exp = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type="expense"')->fetchColumn();
            return "From your finance records: KES " . number_format($inc, 0) . " income vs KES " . number_format($exp, 0) . " expenses, giving an estimated net of KES " . number_format($inc - $exp, 0) . ". Use the Reports tab for a full breakdown.";
        }
        // ── Workers / labour ──
        if (str_contains($q, 'worker') || str_contains($q, 'staff') || str_contains($q, 'labour') || str_contains($q, 'labour')) {
            $c = (int)$pdo->query('SELECT COUNT(*) FROM workers WHERE status="active"')->fetchColumn();
            return "You have " . $c . " active workers in your records. Track attendance and wages in the Labour module so labour costs never surprise you.";
        }
        // ── Flocks / animals ──
        if (str_contains($q, 'flock') || str_contains($q, 'animal') || str_contains($q, 'bird') || str_contains($q, 'herd') || str_contains($q, 'livestock')) {
            $f = (int)$pdo->query('SELECT COUNT(*) FROM flocks WHERE status="active"')->fetchColumn();
            $a = (int)$pdo->query('SELECT COUNT(*) FROM animals WHERE status="alive"')->fetchColumn();
            return "Your livestock records show " . $f . " active flocks and " . $a . " animals alive. Keep daily health and production logs to catch problems early.";
        }
        // ── Upcoming / reminders ──
        if (str_contains($q, 'upcoming') || str_contains($q, 'remind') || str_contains($q, 'due') || str_contains($q, 'next')) {
            $r = $pdo->query('SELECT title, remind_at FROM reminders WHERE status="pending" AND remind_at >= NOW() ORDER BY remind_at ASC LIMIT 5')->fetchAll();
            if (empty($r)) return "Nothing pending in your reminders. Schedule vaccinations, harvests and payments so nothing slips.";
            $lines = array_map(fn($x) => $x['title'] . ' (' . substr($x['remind_at'], 0, 16) . ')', $r);
            return "Upcoming from your reminders: " . implode('; ', $lines) . ".";
        }
        // ── Help / what can you do ──
        if (str_contains($q, 'help') || str_contains($q, 'what can you') || str_contains($q, 'who are you') || str_contains($q, 'hi') || str_contains($q, 'hello')) {
            return "Hi, I'm Wangari's farm assistant. I read your farm's own records, so I can answer questions like: \"How much did I sell today?\", \"Who owes me credit?\", \"Are any feeds low?\", \"What's my profit so far?\", or \"What's coming up this week?\". Try one!";
        }
        // ── Default ──
        return "I read the farm's records and I can help with: sales and revenue, customer credit, feed and stock levels, egg production, profit and expenses, workers, flocks and animals, upcoming reminders, and what's due this week. Try asking one of those. (An AI provider key can be added later for deeper, open-ended answers.)";
    } catch (Exception $e) {
        return "I had trouble reading the records: " . $e->getMessage();
    }
}
?>

<style>
.ai-shell { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.ai-chat { background: #fff; border: 1px solid var(--admin-border); border-radius: 4px; box-shadow: 0 10px 30px rgba(15,23,42,0.03); overflow: hidden; }
.ai-header { background: linear-gradient(135deg, #0B1220 0%, #1B7A3D 100%); color: #fff; padding: 20px 24px; }
.ai-header h1 { margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.35rem; color: #fff; display: flex; align-items: center; gap: 10px; }
.ai-header p { margin: 6px 0 0; color: rgba(255,255,255,0.75); font-size: 0.88rem; }
.ai-messages { padding: 24px; display: flex; flex-direction: column; gap: 14px; min-height: 320px; max-height: 460px; overflow-y: auto; background: #f8fafc; }
.ai-bubble { max-width: 85%; padding: 12px 16px; border-radius: 12px; font-size: 0.92rem; line-height: 1.6; }
.ai-bubble.bot { background: #fff; border: 1px solid var(--admin-border); border-bottom-left-radius: 4px; align-self: flex-start; }
.ai-bubble.user { background: linear-gradient(135deg, #166534, #1B7A3D); color: #fff; border-bottom-right-radius: 4px; align-self: flex-end; }
.ai-input-bar { display: flex; gap: 10px; padding: 16px; border-top: 1px solid var(--admin-border); background: #fff; }
.ai-input-bar input { flex: 1; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.92rem; outline: none; font-family: inherit; }
.ai-input-bar input:focus { border-color: var(--admin-primary); }
.ai-suggestions { display: flex; flex-direction: column; gap: 10px; }
.ai-suggestion { text-align: left; background: #fff; border: 1px solid var(--admin-border); border-radius: 8px; padding: 12px 14px; font-size: 0.85rem; color: var(--admin-text-main); cursor: pointer; transition: all 0.15s; font-family: inherit; }
.ai-suggestion:hover { border-color: var(--admin-primary); background: #f0fdf4; }
@media (max-width: 900px) { .ai-shell { grid-template-columns: 1fr; } }
</style>

<div class="ai-shell">
    <div class="ai-chat">
        <div class="ai-header">
            <h1><i data-lucide="sparkles" style="width:20px;height:20px;"></i> Ask Wangari</h1>
            <p>Your farm assistant. It reads your own records and answers in plain language.</p>
        </div>

        <div class="ai-messages" id="ai-messages">
            <?php if ($asked !== ''): ?>
                <div class="ai-bubble user"><?= htmlspecialchars($asked, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="ai-bubble bot"><?= nl2br(htmlspecialchars($answer, ENT_QUOTES, 'UTF-8')) ?></div>
            <?php else: ?>
                <div class="ai-bubble bot">Hi, I'm Wangari's farm assistant. I read your farm's own records, so I can answer questions like "How much did I sell today?", "Who owes me credit?", or "What's coming up this week?". Ask me anything about your farm.</div>
            <?php endif; ?>
        </div>

        <form method="POST" class="ai-input-bar" id="ai-form">
            <input type="text" name="question" id="ai-question" placeholder="Ask about your farm…" autocomplete="off" required>
            <button type="submit" class="btn btn-primary"><i data-lucide="send" style="width:15px;height:15px;"></i> Ask</button>
        </form>
    </div>

    <div>
        <h3 style="margin:0 0 12px;font-family:'Outfit',sans-serif;font-size:0.95rem;color:var(--admin-text-heading);">Try asking</h3>
        <div class="ai-suggestions" id="ai-suggestions">
            <button type="button" class="ai-suggestion" data-q="How much did I sell this month?">How much did I sell this month?</button>
            <button type="button" class="ai-suggestion" data-q="Who owes me credit right now?">Who owes me credit right now?</button>
            <button type="button" class="ai-suggestion" data-q="Are any feeds or stock low?">Are any feeds or stock low?</button>
            <button type="button" class="ai-suggestion" data-q="What is my profit so far?">What is my profit so far?</button>
            <button type="button" class="ai-suggestion" data-q="How many eggs did I produce today?">How many eggs did I produce today?</button>
            <button type="button" class="ai-suggestion" data-q="What's coming up this week?">What's coming up this week?</button>
        </div>
        <div style="margin-top:16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;font-size:0.82rem;color:#92400e;line-height:1.6;">
            <strong style="display:block;margin-bottom:4px;"><i data-lucide="info" style="width:14px;height:14px;vertical-align:-2px;"></i> How it works</strong>
            Answers come from your own farm records using safe, permission-checked queries. Nothing leaves your data. A cloud AI provider key can be added later for deeper, open-ended answers.
        </div>
    </div>
</div>

<script>
document.getElementById('ai-suggestions').addEventListener('click', function(e){
    const btn = e.target.closest('.ai-suggestion');
    if (!btn) return;
    document.getElementById('ai-question').value = btn.dataset.q;
    document.getElementById('ai-form').requestSubmit();
});
const msgs = document.getElementById('ai-messages');
msgs.scrollTop = msgs.scrollHeight;
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
