<?php
/**
 * AI Farm Assistant — Wangari.
 * Answers questions from the farm's own records. Rule-based + schema-aware
 * handlers keep it instant and offline-capable; a cloud LLM provider key can
 * be added later for open-ended questions (settings).
 *
 * v2: expanded handlers (milk, crops, tasks, feeding, mortality, this-week),
 * structured {answer, suggestions} responses, and async JSON mode for the
 * chat UI (typing indicator + follow-up chips).
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/Frontend/pages/login.php';</script>"; exit;
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../includes/config.php';
}
$page_title = 'AI Farm Assistant - Admin';
$pdo = getDB();

/* Smart local engine: calculators, math evaluator, knowledge base (no LLM). */
require_once __DIR__ . '/../../Backend/config/ai_engine.php';
if ($pdo) aiSeedKnowledge($pdo);

/* ── Thinking mode: per-user toggle, LLM takes over the chatbox ── */
if (!isset($_SESSION['ai_thinking'])) $_SESSION['ai_thinking'] = 0;
$thinkingEnabled = (int)($_SESSION['ai_thinking'] ?? 0) === 1;
$aiProvider = $pdo ? aiSetting($pdo, 'ai_provider') : '';
$aiApiKey   = $pdo ? aiSetting($pdo, 'ai_api_key') : '';
$aiModel    = $pdo ? aiSetting($pdo, 'ai_model') : '';
$aiConnected = $aiProvider !== '' && $aiApiKey !== '';
$thinkingActive = $aiConnected && $thinkingEnabled;

/* Toggle Thinking (POST from the switch) — verify CSRF here because this exits
 * before the header's global CSRF check runs. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'toggle_thinking') {
    $tok = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (function_exists('verifyCSRFToken') && verifyCSRFToken((string)$tok)) {
        $_SESSION['ai_thinking'] = ($_POST['thinking'] ?? '') === '1' ? 1 : 0;
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/**
 * Route one question: Thinking ON → LLM first (if connected), local engine fallback.
 * Thinking OFF → local engine only (fast, offline, private).
 */
function routeAIQuestion(PDO $pdo, string $q, bool $thinking): array
{
    if ($thinking) {
        $llm = aiLLM($pdo, $q);
        if ($llm !== null) {
            try {
                $pdo->prepare('INSERT INTO ai_chat_logs (user_id, question, answer, mode) VALUES (?,?,?,"llm")')
                    ->execute([(int)($_SESSION['user_id'] ?? 0), $q, $llm['answer']]);
            } catch (Exception $e) { /* ignore */ }
            return $llm;
        }
    }
    $res = answerFarmQuestion($pdo, $q);
    try {
        $pdo->prepare('INSERT INTO ai_chat_logs (user_id, question, answer, mode) VALUES (?,?,?,"local")')
            ->execute([(int)($_SESSION['user_id'] ?? 0), $q, $res['answer']]);
    } catch (Exception $e) { /* ignore */ }
    return $res;
}

/* ── Async JSON mode: answer and exit BEFORE rendering the page ──
 * The admin header renders HTML, so AJAX requests must be answered first.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $q = trim($_POST['question'] ?? '');
    if ($q !== '') {
        $thinking = ($_POST['thinking'] ?? '') === '1';
        $res = routeAIQuestion($pdo, $q, $thinking && $aiConnected);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'answer' => $res['answer'], 'suggestions' => $res['suggestions'], 'thinking' => $thinking]);
        exit;
    }
}

/* Minimal head — AI assistant has its own full-screen dark layout, no admin sidebar/topbar */
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        :root {
            --admin-primary: #166534;
            --admin-primary-light: #1B7A3D;
            --admin-accent: #22C55E;
            --admin-dark: #0B1220;
            --admin-body-bg: #FAFBFC;
            --admin-border: #E7EAF0;
            --admin-card-bg: #FFFFFF;
            --admin-text-main: #334155;
            --admin-text-heading: #0F172A;
            --admin-text-muted: #64748B;
            --w2-primary: #166534;
            --w2-lime: #22C55E;
            --w2-ink: #0B1220;
            --w2-border: #E7EAF0;
            --w2-shadow: 0 4px 20px rgba(15,23,42,0.04);
        }
        html, body { margin: 0; padding: 0; font-family: 'Inter Tight', sans-serif; background: #0B1220; height: 100%; overflow: hidden; }
    </style>
</head>
<body>
<?php
/* ── Ask handler (page render) ── */

/* ── Ask handler (page render) ── */
$answer = ''; $asked = ''; $followups = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && ($_POST['_action'] ?? '') !== 'toggle_thinking') {
    $question = trim($_POST['question'] ?? '');
    if ($question !== '') {
        $asked = $question;
        $res = routeAIQuestion($pdo, $question, $thinkingEnabled && $aiConnected);
        $answer = $res['answer'];
        $followups = $res['suggestions'];
    }
}

/**
 * Answer a natural-language question by querying the farm's own data.
 * Returns ['answer' => string, 'suggestions' => string[]].
 * Safe, permission-checked, parameterized. Falls back to a helpful default.
 */
function answerFarmQuestion(PDO $pdo, string $q): array
{
    $q = strtolower($q);
    $defaultSuggestions = [
        'How much did I sell this month?',
        'Who owes me credit right now?',
        'How much feed do 50 broilers need for 10 days?',
        'What is my break-even price for 200 broilers?',
        'Vaccination schedule for cattle?',
        'What is coming up this week?',
    ];
    try {
        // ── 1. Smart local engine FIRST: calculators, math, knowledge base ──
        // These are specific (numbers, operators, named topics) so they win
        // over the generic data handlers below (e.g. "feed" vs "how much feed").
        $smart = aiFarmCalc($q);
        if ($smart !== null) return $smart;
        $kb = aiKnowledge($pdo, $q);
        if ($kb !== null) return $kb;
        // ── Sales / revenue ──
        if (str_contains($q, 'sales') || str_contains($q, 'revenue') || str_contains($q, 'income') || str_contains($q, 'sold') || str_contains($q, 'sell') || str_contains($q, 'earned') || str_contains($q, 'order')) {
            $month = date('Y-m');
            $row = $pdo->query('SELECT COALESCE(SUM(total_amount),0) AS t, COUNT(*) AS c FROM orders WHERE status IN ("paid","completed")')->fetch();
            $today = $pdo->query('SELECT COALESCE(SUM(total_amount),0) AS t FROM orders WHERE DATE(created_at)=CURDATE() AND status IN ("paid","completed")')->fetch();
            $monthly = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) AS t FROM orders WHERE DATE_FORMAT(created_at,"%Y-%m")=? AND status IN ("paid","completed")');
            $monthly->execute([$month]);
            $m = $monthly->fetch();
            return ['answer' => "From your records: KES " . number_format((float)$m['t'], 0) . " in completed sales this month (" . $month . "), KES " . number_format((float)$today['t'], 0) . " today, and " . (int)$row['c'] . " completed orders all-time.", 'suggestions' => ['Who owes me credit right now?', 'What is my profit so far?', 'What sold the most this month?']];
        }
        // ── Best sellers (only when clearly about products/sales) ──
        if ((str_contains($q, 'best') || str_contains($q, 'top') || str_contains($q, 'most')) && (str_contains($q, 'sell') || str_contains($q, 'sold') || str_contains($q, 'product') || str_contains($q, 'revenue') || str_contains($q, 'seller') || str_contains($q, 'performing') || str_contains($q, 'moving'))) {
            $stmt = $pdo->query('SELECT p.name, SUM(oi.quantity) AS qty, SUM(oi.price_at_purchase * oi.quantity) AS rev FROM order_items oi JOIN products p ON p.id=oi.product_id GROUP BY oi.product_id, p.name ORDER BY rev DESC LIMIT 5');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) return ['answer' => "No product sales found yet — once you record orders, I can tell you what sells best.", 'suggestions' => $defaultSuggestions];
            $lines = array_map(fn($r) => $r['name'] . " (KES " . number_format((float)$r['rev'], 0) . ")", $rows);
            return ['answer' => "Your top sellers by revenue: " . implode('; ', $lines) . ".", 'suggestions' => ['How much did I sell this month?', 'Are any feeds or stock low?']];
        }
        // ── Credit / debtors ──
        if (str_contains($q, 'credit') || str_contains($q, 'debt') || str_contains($q, 'owe') || str_contains($q, 'owing')) {
            $rows = $pdo->query('SELECT * FROM customer_credits')->fetchAll();
            $total = 0; $names = [];
            foreach ($rows as $r) {
                $bal = (float)($r['balance'] ?? 0);
                if ($bal > 0) { $total += $bal; $names[] = ($r['customer_name'] ?: 'customer') . ' (KES ' . number_format($bal, 0) . ')'; }
            }
            if ($total <= 0) return ['answer' => "Good news: no outstanding customer credit in your records right now.", 'suggestions' => ['What is coming up this week?', 'How much did I sell this month?']];
            return ['answer' => "You have KES " . number_format($total, 0) . " outstanding in credit. Top balances: " . implode(', ', array_slice($names, 0, 5)) . ". Consider a follow-up reminder for these customers.", 'suggestions' => ['Schedule follow-ups for credit customers', 'What is coming up this week?']];
        }
        // ── Feed / stock ──
        if (str_contains($q, 'feed') || str_contains($q, 'stock') || str_contains($q, 'inventory') || str_contains($q, 'low') || str_contains($q, 'raw material') || str_contains($q, 'ingredient')) {
            $alerts = [];
            try { $alerts = $pdo->query('SELECT * FROM system_alerts WHERE alert_type="low_stock" AND status="active" LIMIT 8')->fetchAll(); } catch (Exception $e) {}
            if (!empty($alerts)) {
                $lines = array_map(fn($a) => ($a['message'] ?? 'item'), $alerts);
                return ['answer' => "You have " . count($alerts) . " active low-stock alerts: " . implode(', ', $lines) . ". Restock soon to avoid stopping production.", 'suggestions' => ['View Inventory Alerts', 'What is my profit so far?']];
            }
            try {
                $count = (int)$pdo->query('SELECT COUNT(*) FROM raw_materials')->fetchColumn();
                return ['answer' => "Stock looks healthy in your records: " . $count . " raw materials tracked, and no active low-stock alerts right now.", 'suggestions' => ['What is coming up this week?', 'How much did I sell this month?']];
            } catch (Exception $e) {
                return ['answer' => "I couldn't read your stock levels just now — check the Inventory & Store module.", 'suggestions' => $defaultSuggestions];
            }
        }
        // ── Production (eggs / milk / general) ──
        if (str_contains($q, 'egg') || str_contains($q, 'production') || str_contains($q, 'produced') || str_contains($q, 'produce') || str_contains($q, 'milk') || str_contains($q, 'litre')) {
            try {
                $today = (float)$pdo->query('SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE DATE(record_date)=CURDATE()')->fetchColumn();
                $month = (float)$pdo->query('SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE DATE_FORMAT(record_date,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m")')->fetchColumn();
                $milk = (float)$pdo->query('SELECT COALESCE(SUM(meat_weight_kg),0) FROM production_records WHERE DATE(record_date)=CURDATE()')->fetchColumn();
            } catch (Exception $e) { $today = 0; $month = 0; $milk = 0; }
            if (str_contains($q, 'milk') || str_contains($q, 'litre')) {
                return ['answer' => "Milk/liquid volume recorded today: " . number_format($milk, 1) . " units. Remember: the Daily Production module captures eggs per flock — for per-cow milk records we log weight/milk in the notes or production field.", 'suggestions' => ['How many eggs did I produce today?', 'How much did I sell this month?']];
            }
            return ['answer' => "Production from your records: " . number_format($today, 0) . " eggs captured today, " . number_format($month, 0) . " this month. Daily capture keeps your profit picture accurate.", 'suggestions' => ['How many animals do I have?', 'Are any feeds or stock low?']];
        }
        // ── Profit / expenses ──
        if (str_contains($q, 'profit') || str_contains($q, 'expense') || str_contains($q, 'cost') || str_contains($q, 'spend')) {
            $inc = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM cashbook_entries WHERE type IN ("income","sale","credit_payment")')->fetchColumn();
            $exp = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM cashbook_entries WHERE type IN ("expense","purchase","cost")')->fetchColumn();
            if ($inc == 0 && $exp == 0) {
                try { $inc = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN ("paid","completed")')->fetchColumn(); } catch (Exception $e) {}
                try { $exp = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn(); } catch (Exception $e) {}
            }
            return ['answer' => "From your finance records: KES " . number_format($inc, 0) . " income vs KES " . number_format($exp, 0) . " expenses, giving an estimated net of KES " . number_format($inc - $exp, 0) . ". Use the Reports tab for a full breakdown.", 'suggestions' => ['How much did I sell this month?', 'What is coming up this week?']];
        }
        // ── Workers ──
        if (str_contains($q, 'worker') || str_contains($q, 'staff') || str_contains($q, 'labour') || str_contains($q, 'labour') || str_contains($q, 'employee')) {
            try { $c = (int)$pdo->query('SELECT COUNT(*) FROM workers WHERE status="active"')->fetchColumn(); }
            catch (Exception $e) { $c = 0; }
            return ['answer' => "You have " . $c . " active workers in your records. Track attendance and wages in the Labour module so labour costs never surprise you.", 'suggestions' => ['What is my profit so far?', 'What is coming up this week?']];
        }
        // ── Flocks / animals / herds ──
        if (str_contains($q, 'flock') || str_contains($q, 'animal') || str_contains($q, 'bird') || str_contains($q, 'chicken') || str_contains($q, 'hen') || str_contains($q, 'herd') || str_contains($q, 'livestock') || str_contains($q, 'cow') || str_contains($q, 'sheep') || str_contains($q, 'goat')) {
            $f = 0; $a = 0;
            try { $f = (int)$pdo->query('SELECT COUNT(*) FROM flocks WHERE status="active"')->fetchColumn(); } catch (Exception $e) {}
            try { $a = (int)$pdo->query('SELECT COUNT(*) FROM animals WHERE status IN ("alive","Active")')->fetchColumn(); } catch (Exception $e) {}
            $byType = '';
            try {
                $rows = $pdo->query('SELECT type, COUNT(*) AS c FROM animals WHERE status IN ("alive","Active") GROUP BY type ORDER BY c DESC LIMIT 4')->fetchAll();
                if ($rows) $byType = ' — ' . implode(', ', array_map(fn($r) => $r['type'] . ' x' . $r['c'], $rows));
            } catch (Exception $e) {}
            return ['answer' => "Your livestock records show " . $f . " active flocks and " . $a . " animals alive" . $byType . ". Keep daily health and production logs to catch problems early.", 'suggestions' => ['Are any feeds or stock low?', 'What is coming up this week?']];
        }
        // ── Crops / fields ──
        if (str_contains($q, 'crop') || str_contains($q, 'field') || str_contains($q, 'planting') || str_contains($q, 'harvest') || str_contains($q, 'acre')) {
            $fields = 0; $plantings = 0;
            try { $fields = (int)$pdo->query('SELECT COUNT(*) FROM fields WHERE status="active"')->fetchColumn(); } catch (Exception $e) {}
            try { $plantings = (int)$pdo->query('SELECT COUNT(*) FROM crop_plantings')->fetchColumn(); } catch (Exception $e) {}
            return ['answer' => "You have " . $fields . " active fields and " . $plantings . " plantings on record. The Crops & Fields module tracks plantings, activities and harvests per field.", 'suggestions' => ['What is coming up this week?', 'Are any feeds or stock low?']];
        }
        // ── Upcoming / reminders ──
        if (str_contains($q, 'upcoming') || str_contains($q, 'remind') || str_contains($q, 'due') || str_contains($q, 'next') || str_contains($q, 'this week') || str_contains($q, 'week')) {
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            $lines = [];
            try {
                $r = $pdo->prepare('SELECT title, remind_at FROM reminders WHERE DATE(remind_at) BETWEEN ? AND ? ORDER BY remind_at ASC LIMIT 8');
                $r->execute([$weekStart, $weekEnd]);
                foreach ($r->fetchAll() as $x) $lines[] = $x['title'] . ' (' . substr($x['remind_at'], 0, 16) . ')';
            } catch (Exception $e) {}
            try {
                $r = $pdo->prepare('SELECT note AS title, due_date AS remind_at FROM crm_followups WHERE DATE(due_date) BETWEEN ? AND ? AND status="open" ORDER BY due_date ASC LIMIT 5');
                $r->execute([$weekStart, $weekEnd]);
                foreach ($r->fetchAll() as $x) $lines[] = 'Follow-up: ' . $x['title'] . ' (' . substr($x['remind_at'], 0, 10) . ')';
            } catch (Exception $e) {}
            if (empty($lines)) return ['answer' => "Nothing pending in your reminders this week. Schedule vaccinations, harvests and payments so nothing slips.", 'suggestions' => ['How much did I sell this month?', 'Are any feeds or stock low?']];
            return ['answer' => "Upcoming this week: " . implode('; ', $lines) . ".", 'suggestions' => ['Schedule a new reminder', 'How many animals do I have?']];
        }
        // ── App help ("how do I...") — answers from the product's own guide ──
        if (str_contains($q, 'how do i') || str_contains($q, 'how to') || str_contains($q, 'help') || str_contains($q, 'where') || str_contains($q, 'add a') || str_contains($q, 'add an') || str_contains($q, 'what can you')) {
            $guide = [];
            if (str_contains($q, 'cow') || str_contains($q, 'animal') || str_contains($q, 'livestock')) {
                $guide = ['Go to Farm Operations → Animals List, tap + Add Animal, fill the tag, species, breed and birth date, then Save.', 'You can also group animals into herds under Farm Operations → Herds / Pens.'];
            } elseif (str_contains($q, 'flock') || str_contains($q, 'chicken') || str_contains($q, 'bird')) {
                $guide = ['Go to Farm Operations → Flocks / Herds, tap + Add Flock, set the breed, initial count and hatch date, then Save.', 'Log daily eggs under Farm Operations → Daily Production.'];
            } elseif (str_contains($q, 'sale') || str_contains($q, 'sell') || str_contains($q, 'order')) {
                $guide = ['Record orders under Sales & Finance → Customer Orders, or walk-in sales under Sales & Finance.', 'For credit sales use the Credit module — it tracks balances and overdue payments.'];
            } elseif (str_contains($q, 'worker') || str_contains($q, 'staff') || str_contains($q, 'labour')) {
                $guide = ['Go to People → Labour & Workers, add workers under Workers, then record daily attendance and wage payments.'];
            } elseif (str_contains($q, 'crop') || str_contains($q, 'field') || str_contains($q, 'plant')) {
                $guide = ['Go to Crops & Fields, add your field under Fields, then record plantings, activities and harvests.'];
            } elseif (str_contains($q, 'remind') || str_contains($q, 'remember') || str_contains($q, 'due')) {
                $guide = ['Go to Tools → Reminders & Weather, tap + Add Reminder, pick a channel (In-app, WhatsApp, SMS or Email) and when it should fire.'];
            } elseif (str_contains($q, 'import') || str_contains($q, 'excel') || str_contains($q, 'spreadsheet') || str_contains($q, 'csv')) {
                $guide = ['Use Tools → Bulk Import/Export to download CSV templates and upload your data in bulk.'];
            } else {
                $guide = ['Open the ? guide in the top bar for a walkthrough of every module.', 'Or use the Ctrl+K search to jump straight to any page.', 'Try asking me things like "How much did I sell this month?" or "Who owes me credit?"'];
            }
            return ['answer' => "Here's how: " . implode(' ', $guide), 'suggestions' => $defaultSuggestions];
        }
        // ── Greeting (word-boundary so "chickens" doesn't match "hi") ──
        if (preg_match('/\b(hi|hello|hey|good (morning|afternoon|evening))\b/', $q) || $q === '') {
            return ['answer' => "Hi, I'm Wangari's farm assistant. I read your farm's own records AND I can think — ask me for a calculation (\"What is 30*1600?\"), a farming question (\"Vaccination schedule for cattle?\", \"What is my FCR?\"), or anything about your farm (\"How much did I sell today?\", \"Who owes me credit?\", \"What's coming up this week?\"). Try one!", 'suggestions' => $defaultSuggestions];
        }
        // ── Fallback ──
        return ['answer' => "I can help three ways:\n\n1️⃣ **Your farm records** — sales, credit, stock, production, profit, workers, animals, crops, this week's reminders.\n2️⃣ **Calculations** — feed rations, batch profit, break-even price, FCR, medication doses, mortality %, wages, egg/milk value, and any math (\"What is (1500+400)*50/1000?\").\n3️⃣ **Farming knowledge** — vaccinations, deworming, common diseases (Newcastle, ECF, mastitis, coccidiosis...), feeding rates, biosecurity, withdrawal periods.\n\nTry one of the suggestions below, or ask me \"How do I add a cow?\" and I'll walk you through it.", 'suggestions' => $defaultSuggestions];
    } catch (Exception $e) {
        return ['answer' => "I had trouble reading the records: " . $e->getMessage(), 'suggestions' => $defaultSuggestions];
    }
}

/**
 * "Today at a glance" digest — used by the dashboard card.
 * Returns a short multi-line plain-text summary of today's key numbers.
 */
?>

<style>
/* ═══ Ask Wangari — Gemini-style minimal chat ═══ */
.ai-page { background: var(--w2-ink, #0B1220); color: #e7e7e7; margin: 0; padding: 0; min-height: 100vh; height: 100vh; display: flex; flex-direction: column; font-family: 'Inter Tight', 'Google Sans', system-ui, sans-serif; overflow: hidden; }

/* ── minimal top bar ── */
.ai-topbar { display: flex; align-items: center; justify-content: space-between; padding: 18px 28px; }
.ai-brand { display: flex; align-items: center; gap: 9px; font-size: 0.92rem; font-weight: 700; color: #f1f1f1; letter-spacing: -0.01em; }
.ai-brand i { color: var(--w2-lime, #22C55E); }
.ai-topright { display: flex; align-items: center; gap: 10px; }
.ai-conn-badge { display: inline-flex; align-items: center; gap: 7px; background: linear-gradient(135deg, #14532D 0%, #1B7A3D 100%); border: none; color: #fff; font-size: 0.78rem; font-weight: 700; padding: 9px 18px; border-radius: 999px; box-shadow: 0 4px 14px rgba(22,101,52,0.25); transition: all .2s; }

/* ── scrollable middle (welcome + messages) ── */
.ai-scroll { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }

/* ── welcome / empty state (Gemini-style big headline + chips) ── */
.ai-welcome { margin: auto; max-width: 760px; width: 100%; padding: 40px 24px 24px; text-align: center; }
.ai-welcome h1 { font-size: clamp(1.6rem, 3.4vw, 2.3rem); font-weight: 700; color: #fff; letter-spacing: -0.03em; margin: 0 0 10px; font-family: 'Inter Tight', sans-serif; }
.ai-welcome p { color: #9ca3af; font-size: 0.95rem; margin: 0 auto 28px; max-width: 520px; line-height: 1.55; }
.ai-chiprow { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.ai-chiprow .ai-suggestion { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.13); color: #d7d7d7; border-radius: 999px; padding: 9px 16px; font-size: 0.8rem; cursor: pointer; transition: all .15s; font-family: inherit; }
.ai-chiprow .ai-suggestion:hover { background: var(--w2-lime, #22C55E); border-color: var(--w2-lime, #22C55E); color: #0B1220; box-shadow: 0 4px 16px rgba(34,197,94,0.3); }
.ai-chiprow .ai-suggestion:active { transform: scale(0.97); box-shadow: 0 2px 8px rgba(34,197,94,0.2); }

/* ── messages: clean rows, no heavy bubbles (Gemini look) ── */
.ai-messages { width: 100%; max-width: 760px; margin: 0 auto; padding: 12px 24px 24px; display: flex; flex-direction: column; gap: 22px; }
.ai-bubble { max-width: 88%; font-size: 0.98rem; line-height: 1.7; }
.ai-messages .ai-bubble.bot { align-self: flex-start; color: #ececec; background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; border-radius: 0 !important; }
.ai-messages .ai-bubble.user { align-self: flex-end; color: #b7c2cd; text-align: right; background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; border-radius: 0 !important; }
.ai-typing { display: inline-flex; gap: 5px; align-items: center; padding: 6px 2px; }
.ai-typing span { width: 7px; height: 7px; border-radius: 50%; background: #5b6472; animation: aiBlink 1.2s infinite; }
.ai-typing span:nth-child(2) { animation-delay: .2s; } .ai-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes aiBlink { 0%,80%,100% { opacity:.3; transform: translateY(0); } 40% { opacity:1; transform: translateY(-3px); } }

/* Response metadata */
.ai-response-meta {
    font-size: 0.72rem;
    color: #64748B;
    margin-top: 6px;
    padding: 4px 0;
    border-top: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── follow-up chips under answers ── */
.ai-followups { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.ai-followup { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #d7d7d7; border-radius: 999px; padding: 7px 14px; font-size: 0.78rem; cursor: pointer; transition: all .15s; font-family: inherit; }
.ai-followup:hover { background: var(--w2-lime, #22C55E); border-color: var(--w2-lime, #22C55E); color: #0B1220; box-shadow: 0 4px 16px rgba(34,197,94,0.3); }

/* ── composer (rounded, Gemini-like) ── */
.ai-composer-wrap { padding: 14px 24px 20px; }
.ai-composer { max-width: 760px; margin: 0 auto; display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.16); border-radius: 26px; padding: 7px 7px 7px 20px; transition: border-color .25s, box-shadow .25s; }
.ai-composer:focus-within { border-color: rgba(255,255,255,0.35); box-shadow: 0 0 0 4px rgba(255,255,255,0.06); }
.ai-composer input { flex: 1; background: transparent; border: none; outline: none; color: #f1f1f1; font-size: 1rem; font-family: inherit; padding: 9px 0; }
.ai-composer input::placeholder { color: #7c8694; }
.ai-send { height: 40px; padding: 0 18px; border: none; border-radius: 999px; background: linear-gradient(135deg, #14532D 0%, #1B7A3D 100%); color: #fff; font-family: inherit; font-weight: 700; font-size: 0.88rem; display: inline-flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer; transition: all .2s; flex-shrink: 0; box-shadow: 0 4px 14px rgba(22,101,52,0.25); }
.ai-send:hover { background: linear-gradient(135deg, #1B7A3D 0%, #22A34B 100%); box-shadow: 0 6px 20px rgba(22,101,52,0.32); transform: translateY(-1px); }
.ai-send:active { transform: translateY(0); }
.ai-send i { width: 16px; height: 16px; }

/* ── mode line under composer ── */
.ai-mode-line { max-width: 760px; margin: 10px auto 0; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.ai-mode-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; font-size: 0.72rem; font-weight: 700; padding: 5px 12px; border-radius: 999px; transition: all .3s; }
.ai-mode-badge .ai-mode-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; opacity: .9; }
.ai-disc { color: #5b6472; font-size: 0.72rem; }

/* ── LLM / Thinking mode: Wangari lime accent (brand system color) ── */
.ai-page.llm-mode .ai-composer { border-color: rgba(34,197,94,0.5); box-shadow: 0 0 0 4px rgba(34,197,94,0.12), 0 6px 30px rgba(34,197,94,0.14); }
.ai-page.llm-mode .ai-composer:focus-within { border-color: var(--w2-lime, #22C55E); }
.ai-page.llm-mode .ai-mode-badge { background: rgba(34,197,94,0.14); border-color: rgba(34,197,94,0.4); color: var(--w2-lime, #22C55E); }
.ai-page.llm-mode .ai-brand i { color: var(--w2-lime, #22C55E); }

/* ── thinking toggle (minimal pill in top bar) ── */
.ai-toggle-pill { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.13); padding: 7px 14px; border-radius: 999px; cursor: pointer; user-select: none; transition: all .2s; }
.ai-toggle-pill:hover { background: rgba(255,255,255,0.1); }
.ai-toggle-pill .ai-toggle-ic { color: #9ca3af; font-size: 0.8rem; font-weight: 700; }
.ai-toggle-pill.on .ai-toggle-ic { color: var(--w2-lime, #22C55E); }
.ai-toggle-pill .ai-toggle-track { position: relative; width: 34px; height: 18px; background: rgba(255,255,255,0.18); border-radius: 999px; transition: background .2s; }
.ai-toggle-pill.on .ai-toggle-track { background: linear-gradient(135deg, var(--w2-primary, #166534), var(--w2-lime, #22C55E)); }
.ai-toggle-pill .ai-toggle-track span { position: absolute; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: #fff; transition: all .2s; }
.ai-toggle-pill.on .ai-toggle-track span { right: 2px; }
.ai-toggle-pill .ai-toggle-track span.off { left: 2px; }

@media (max-width: 700px) {
    .ai-page { margin: -16px; min-height: calc(100vh - 40px); }
    .ai-welcome h1 { font-size: 1.4rem; }
    .ai-mode-line { justify-content: center; }
}
</style>

<div class="ai-page<?= $thinkingActive ? ' llm-mode' : '' ?>" id="ai-chat">
    <!-- minimal top bar -->
    <div class="ai-topbar">
        <div class="ai-brand"><i data-lucide="sparkles" style="width:17px;height:17px;"></i> Ask Wangari</div>
        <div class="ai-topright">
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="ai-toggle-pill" style="text-decoration:none;" title="Back to Dashboard">
                <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
                <span style="font-size:0.78rem;font-weight:700;color:#b7c2cd;">Home</span>
            </a>
            <?php if ($aiConnected): ?>
            <form method="post" id="thinking-form" style="margin:0;">
                <input type="hidden" name="_action" value="toggle_thinking">
                <input type="hidden" name="thinking" id="thinking-val" value="<?= $thinkingEnabled ? '1' : '0' ?>">
                <label class="ai-toggle-pill<?= $thinkingEnabled ? ' on' : '' ?>" id="ai-toggle-pill">
                    <i data-lucide="brain" class="ai-toggle-ic" style="width:15px;height:15px;"></i>
                    <span style="font-size:0.78rem;font-weight:700;color:<?= $thinkingEnabled ? '#22C55E' : '#b7c2cd' ?>;">Thinking</span>
                    <span class="ai-toggle-track"><span class="<?= $thinkingEnabled ? '' : 'off' ?>"></span></span>
                </label>
            </form>
            <?php else: ?>
            <a href="<?= BASE_URL ?>admin/connectors.php?tab=ai" class="ai-conn-badge" style="text-decoration:none;">
                <i data-lucide="plug" style="width:14px;height:14px;"></i> Connect AI
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="ai-scroll">
        <!-- welcome / empty state -->
        <?php if ($asked === ''): ?>
        <div class="ai-welcome" id="ai-welcome">
            <h1>What's happening on your farm today?</h1>
            <p>Ask Wangari anything — your records, the math, or farming know-how. It answers in plain language.</p>
            <div class="ai-chiprow">
                <button type="button" class="ai-suggestion" data-q="How much did I sell this month?">How much did I sell this month?</button>
                <button type="button" class="ai-suggestion" data-q="Who owes me credit right now?">Who owes me credit right now?</button>
                <button type="button" class="ai-suggestion" data-q="What is my profit so far?">What is my profit so far?</button>
                <button type="button" class="ai-suggestion" data-q="What's coming up this week?">What's coming up this week?</button>
                <button type="button" class="ai-suggestion" data-q="How much feed do 50 broilers need for 10 days?">How much feed do 50 broilers need?</button>
                <button type="button" class="ai-suggestion" data-q="What is 30 * 1600 + 500?">Math: 30 × 1600 + 500?</button>
                <button type="button" class="ai-suggestion" data-q="Vaccination schedule for cattle?">Vaccination schedule for cattle?</button>
                <button type="button" class="ai-suggestion" data-q="How do I add a cow?">How do I add a cow?</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- conversation -->
        <div class="ai-messages" id="ai-messages" <?= $asked === '' ? 'style="display:none;"' : '' ?>>
            <?php if ($asked !== ''): ?>
                <div class="ai-bubble user"><?= htmlspecialchars($asked, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="ai-bubble bot"><?= nl2br(htmlspecialchars($answer, ENT_QUOTES, 'UTF-8')) ?>
                    <?php if (!empty($followups)): ?>
                        <div class="ai-followups"><?php foreach ($followups as $sug): ?><button type="button" class="ai-followup" data-q="<?= htmlspecialchars($sug, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sug, ENT_QUOTES, 'UTF-8') ?></button><?php endforeach; ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- composer (Gemini-style) -->
    <div class="ai-composer-wrap">
        <form class="ai-composer" id="ai-form">
            <input type="text" name="question" id="ai-question" placeholder="Ask Wangari…" autocomplete="off" required>
            <button type="submit" class="ai-send" aria-label="Send">Send <i data-lucide="arrow-up"></i></button>
        </form>
        <div class="ai-mode-line">
            <span class="ai-mode-badge" id="ai-mode-badge">
                <span class="ai-mode-dot"></span>
                <span id="ai-mode-label">Wangari - Ox Alpha</span>
            </span>
            <span class="ai-disc">Wangari can make mistakes — check important numbers in your records.</span>
        </div>
    </div>
</div>

<script>
(function(){
    var form = document.getElementById('ai-form');
    var input = document.getElementById('ai-question');
    var msgs = document.getElementById('ai-messages');
    var askToken = (window.WangariAdmin && window.WangariAdmin.csrfToken) || document.querySelector('meta[name="csrf-token"]')?.content || '';
    var thinkingOn = <?= $aiConnected && $thinkingEnabled ? 'true' : 'false' ?>;

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function addBubble(text, who, meta) {
        var d = document.createElement('div');
        d.className = 'ai-bubble ' + who;
        // Render newlines and **bold** safely (used by calculator answers).
        var esc = escapeHtml(text);
        esc = esc.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        esc = esc.replace(/\n/g, '<br>');
        d.innerHTML = esc;
        
        // Add metadata for bot messages
        if (who === 'bot' && meta) {
            var metaDiv = document.createElement('div');
            metaDiv.className = 'ai-response-meta';
            var parts = [];
            if (meta.response_time_ms) parts.push(meta.response_time_ms + 'ms');
            if (meta.tokens_used !== undefined) parts.push(meta.tokens_used + '/' + meta.tokens_limit + ' tokens');
            if (meta.model) parts.push(meta.model);
            if (parts.length) {
                metaDiv.textContent = parts.join(' • ');
                d.appendChild(metaDiv);
            }
        }
        
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    }
    function addTyping() {
        var d = document.createElement('div');
        d.className = 'ai-bubble bot ai-typing';
        d.innerHTML = '<span></span><span></span><span></span>';
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    }
    function addFollowups(sugs) {
        var wrap = document.createElement('div');
        wrap.className = 'ai-followups';
        sugs.forEach(function(s) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'ai-followup'; b.dataset.q = s; b.textContent = s;
            wrap.appendChild(b);
        });
        msgs.appendChild(wrap);
        msgs.scrollTop = msgs.scrollHeight;
    }
    function ask(q) {
        if (!q) return;
        input.value = '';
        // First message: swap the welcome headline for the conversation
        var welcome = document.getElementById('ai-welcome');
        if (welcome) { welcome.style.display = 'none'; welcome.remove(); }
        msgs.style.display = '';
        addBubble(q, 'user');
        var typing = addTyping();
        var fd = new FormData();
        fd.append('question', q);
        fd.append('thinking', thinkingOn ? '1' : '0');
        if (askToken) fd.append('csrf_token', askToken);
        fetch(window.location.href, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(j){
                typing.remove();
                if (j && j.success) {
                    addBubble(j.answer, 'bot', j.metadata);
                    if (j.suggestions && j.suggestions.length) addFollowups(j.suggestions);
                } else {
                    addBubble('Sorry, I could not read the records just now. Please try again.', 'bot');
                }
            })
            .catch(function(){
                typing.remove();
                addBubble('Network error — please try again.', 'bot');
            });
    }
    // Thinking switch: flip the value first, then submit (the old code sent the
    // current value, so the toggle never changed — fixed by toggling it here).
    var thinkingForm = document.getElementById('thinking-form');
    function setLLMVisual(on) {
        var chat = document.getElementById('ai-chat');
        var badge = document.getElementById('ai-mode-badge');
        var label = document.getElementById('ai-mode-label');
        var pill = document.getElementById('ai-toggle-pill');
        if (chat) chat.classList.toggle('llm-mode', on);
        if (label) label.textContent = on
            ? '🧠 LLM active — <?= htmlspecialchars(ucfirst($aiProvider)) ?><?= $aiModel !== '' ? ' (' . htmlspecialchars($aiModel) . ')' : '' ?>'
            : 'Wangari - Ox Alpha';
        if (badge) badge.style.background = on ? 'rgba(34,197,94,0.14)' : '';
        if (pill) {
            pill.classList.toggle('on', on);
            var knob = pill.querySelector('.ai-toggle-track span');
            if (knob) knob.className = on ? '' : 'off';
            var txt = pill.querySelector('span[style*="font-size:0.78rem"]');
            if (txt) txt.style.color = on ? '#22C55E' : '#b7c2cd';
        }
    }
    if (thinkingForm) {
        thinkingForm.addEventListener('submit', function(e){ e.preventDefault(); });
        thinkingForm.querySelector('label').addEventListener('click', function(e){
            e.preventDefault();
            var v = document.getElementById('thinking-val');
            var next = (v.value === '1') ? '0' : '1';
            v.value = next;
            thinkingOn = (next === '1');
            setLLMVisual(thinkingOn);
            var tok = askToken || (window.WangariAdmin && window.WangariAdmin.csrfToken) || '';
            var fd = new FormData(thinkingForm);
            if (tok && !fd.has('csrf_token')) fd.append('csrf_token', tok);
            fetch(window.location.href, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ location.reload(); })
                .catch(function(){ location.reload(); });
        });
    }
    form.addEventListener('submit', function(e){ e.preventDefault(); ask(input.value.trim()); });
    document.addEventListener('click', function(e){
        var f = e.target.closest('.ai-followup, .ai-suggestion');
        if (f && f.dataset.q) ask(f.dataset.q);
    });
})();
</script>

<script src="https://unpkg.com/lucide@latest"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{if(typeof lucide!=='undefined')lucide.createIcons();});</script>
</body>
</html>
