<?php
/**
 * Wangari AI Engine — smart, local, free (no LLM, no API, no data leaves the farm).
 *
 * Three capabilities, all deterministic:
 *   1. FARM CALCULATORS  — feed rations, batch profit, break-even price, FCR,
 *                          medication dosing, mortality %, wage bills, egg/milk
 *                          economics. Extracts numbers from natural language.
 *   2. SAFE MATH          — evaluates expressions like "35 * 1600 + 500" without eval().
 *   3. KNOWLEDGE BASE     — seeded practical farming knowledge (vaccinations,
 *                          common diseases, treatments, feeding rates) that any
 *                          farmer can ask about. Lives in the ai_knowledge table.
 */
declare(strict_types=1);

/* ─────────────────────────────────────────────────────────────
 * 1. SAFE MATH EVALUATOR (shunting-yard, no eval())
 * Supports + - * / % ^ ( ) and decimals. Throws on invalid input.
 * ───────────────────────────────────────────────────────────── */
function aiMathEval(string $expr): float
{
    $tokens = aiMathTokenize($expr);
    $output = [];
    $ops = [];
    $prec = ['+' => 2, '-' => 2, '*' => 3, '/' => 3, '%' => 3, '^' => 4];
    foreach ($tokens as $t) {
        if (is_numeric($t)) { $output[] = (float)$t; continue; }
        if ($t === '(') { $ops[] = $t; continue; }
        if ($t === ')') {
            while ($ops && end($ops) !== '(') { $output[] = array_pop($ops); }
            if (!$ops) throw new InvalidArgumentException('Mismatched parentheses');
            array_pop($ops);
            continue;
        }
        // operator
        while ($ops && end($ops) !== '(' && $prec[$t] <= $prec[end($ops)]) {
            $output[] = array_pop($ops);
        }
        $ops[] = $t;
    }
    while ($ops) { if (end($ops) === '(') throw new InvalidArgumentException('Mismatched parentheses'); $output[] = array_pop($ops); }
    $stack = [];
    foreach ($output as $t) {
        if (is_numeric($t)) { $stack[] = (float)$t; continue; }
        $b = array_pop($stack); $a = array_pop($stack);
        if ($a === null || $b === null) throw new InvalidArgumentException('Invalid expression');
        switch ($t) {
            case '+': $stack[] = $a + $b; break;
            case '-': $stack[] = $a - $b; break;
            case '*': $stack[] = $a * $b; break;
            case '/': if ($b == 0) throw new InvalidArgumentException('Cannot divide by zero'); $stack[] = $a / $b; break;
            case '%': $stack[] = fmod($a, $b); break;
            case '^': $stack[] = pow($a, $b); break;
        }
    }
    if (count($stack) !== 1) throw new InvalidArgumentException('Invalid expression');
    return (float)$stack[0];
}

function aiMathTokenize(string $expr): array
{
    $expr = str_replace([',', ' '], '', strtolower(trim($expr)));
    // Guard: only digits, operators, parens, decimal point allowed
    if (!preg_match('/^[0-9+\-*\/%^().]+$/', $expr)) {
        throw new InvalidArgumentException('Only numbers and + - * / % ^ ( ) are allowed');
    }
    $tokens = [];
    $num = '';
    $len = strlen($expr);
    for ($i = 0; $i < $len; $i++) {
        $ch = $expr[$i];
        if (ctype_digit($ch) || $ch === '.') { $num .= $ch; continue; }
        if ($num !== '') { $tokens[] = $num; $num = ''; }
        // support leading minus/plus as unary: push 0 first (e.g. "-5" → 0 - 5)
        if (($ch === '-' || $ch === '+') && (empty($tokens) || end($tokens) === '(')) {
            $tokens[] = '0';
        }
        $tokens[] = $ch;
    }
    if ($num !== '') $tokens[] = $num;
    return $tokens;
}

/* ─────────────────────────────────────────────────────────────
 * 2. FARM CALCULATORS
 * Each returns ['answer'=>string,'suggestions'=>string[]] or null if not a match.
 * ───────────────────────────────────────────────────────────── */
function aiFarmCalc(string $q): ?array
{
    $q = strtolower(trim($q));

    /* Pure math FIRST: "30 * 1600 + 500?" / "what is 1500*40+500" — must contain a digit-operator pair.
       Avoids dates (14-08-2026) by ignoring plain subtraction of date-like numbers. */
    if (preg_match('/\d\s*[*\/%\^]\s*\d/', $q)
        || preg_match('/\d\s*\+\s*\d/', $q)
        || (preg_match('/\d\s*-\s*\d/', $q) && !preg_match('/(\d{4}\s*-\s*\d{1,2}(\s*-\s*\d{1,2})?|\d{1,2}\s*-\s*\d{1,2}\s*-\s*\d{2,4})/', $q))) {
        return aiCalcMath($q);
    }

    /* FCR must come BEFORE feed: "600 kg feed for 300 kg gain" contains "feed for". */
    if (aiHasAny($q, ['fcr', 'feed conversion'])) {
        return aiCalcFCR($q);
    }

    /* Feed requirement: "feed for 50 broilers for 10 days" / "how much feed do 20 layers need" */
    if (count(aiNums($q)) > 0 && aiHasAny($q, ['how much feed', 'feed for', 'feed do', 'feed needed', 'feed required', 'feed ration'])) {
        return aiCalcFeed($q);
    }

    /* Batch profit / break-even: "profit for 200 broilers" / "break even price" */
    if (aiHasAny($q, ['break even', 'breakeven', 'break-even', 'profit for', 'profit on', 'batch profit', 'profit per bird', 'roi'])) {
        return aiCalcProfit($q);
    }

    /* Medication dose: "how much medicine for a 300kg cow" / "dosage" */
    if (aiHasAny($q, ['dosage', 'dose', 'how much medicine', 'how many ml', 'how many cc', 'inject', 'medication for'])) {
        return aiCalcDose($q);
    }

    /* Mortality: "mortality rate" */
    if (aiHasAny($q, ['mortality', 'death rate', 'dead birds', 'death loss'])) {
        return aiCalcMortality($q);
    }

    /* Wages: "wages for 5 workers for 20 days at 600" */
    if (aiHasAny($q, ['wage', 'wages', 'labour cost', 'salary', 'payroll'])) {
        return aiCalcWages($q);
    }

    /* Milk economics FIRST: "value of 40 litres at 50" ("litre" also implies milk) */
    if (aiHasAny($q, ['milk', 'litre', 'liters'])) {
        return aiCalcMilk($q);
    }

    /* Egg economics: "value of 30 crates at 1600" / "how much are 500 eggs worth" */
    if (aiHasAny($q, ['egg', 'crate'])) {
        return aiCalcEggs($q);
    }

    return null;
}

function aiHasAny(string $q, array $needles): bool
{
    foreach ($needles as $n) { if (str_contains($q, $n)) return true; }
    return false;
}

/** Pull all decimal numbers from a question in order (handles 1,600 → 1600). */
function aiNums(string $q): array
{
    $clean = preg_replace('/(\d),(\d{3})/', '$1$2', $q); // merge thousand separators
    preg_match_all('/\d+(?:\.\d+)?/', $clean, $m);
    return array_map('floatval', $m[0]);
}

function aiSuggestCalc(): array
{
    return [
        'How much feed do 50 broilers need for 10 days?',
        'What is my break-even price for 200 broilers?',
        'How much medicine for a 300 kg cow?',
        'What is my FCR if I used 600 kg feed for 300 kg gain?',
        'Value of 30 crates of eggs at KES 1,600 each?',
    ];
}

function aiCalcFeed(string $q): array
{
    $nums = aiNums($q);
    // Defaults: broiler 0.12 kg/day, layer 0.12 kg/day, cow 12 kg/day, sheep/goat 1.5 kg/day
    $rate = 0.12;
    if (aiHasAny($q, ['cow', 'cattle', 'bull', 'dairy'])) $rate = 12;
    elseif (aiHasAny($q, ['sheep', 'goat', 'lamb', 'ram'])) $rate = 1.5;
    elseif (aiHasAny($q, ['layer', 'hen'])) $rate = 0.12;
    elseif (aiHasAny($q, ['broiler', 'kienyeji', 'chicken', 'bird'])) $rate = 0.12;
    elseif (aiHasAny($q, ['pig', 'hog'])) $rate = 2.5;
    if (count($nums) < 1) {
        return ['answer' => "Tell me how many animals and for how many days and I'll work out the feed. Example: \"How much feed do 50 broilers need for 10 days?\"", 'suggestions' => aiSuggestCalc()];
    }
    $count = $nums[0];
    $days = $nums[1] ?? 1;
    $kg = $count * $rate * $days;
    $unit = aiHasAny($q, ['cow', 'cattle', 'bull', 'dairy']) ? 'roughage + concentrate' : 'feed';
    return ['answer' => sprintf(
        "Feed estimate: %d animals × %.2f kg/day × %d days ≈ **%.1f kg** total feed (%.2f kg %s per animal per day). This is an estimate — weigh regularly and adjust.",
        (int)$count, $rate, (int)$days, $kg, $rate, $unit
    ), 'suggestions' => ['What is my break-even price for 200 broilers?', 'What is my FCR if I used 600 kg feed for 300 kg gain?']];
}

function aiCalcProfit(string $q): array
{
    $nums = aiNums($q);
    $birds = $nums[0] ?? 100;
    $costs = array_slice($nums, 1);
    $chickCost = $costs[0] ?? 100;       // per bird or total — interpret total-ish
    $feedCost = $costs[1] ?? 400;
    $medCost = $costs[2] ?? 50;
    $saleKg = $costs[3] ?? 1.8;
    $priceKg = $costs[4] ?? 380;
    // total cost = (chick + feed + med) per bird assumed; revenue = birds * kg * price
    $totalCost = $birds * ($chickCost + $feedCost + $medCost);
    $revenue = $birds * $saleKg * $priceKg;
    $profit = $revenue - $totalCost;
    $roi = $totalCost > 0 ? ($profit / $totalCost) * 100 : 0;
    $breakeven = $totalCost / max($birds * $saleKg, 1);
    $line = "For {$birds} birds (est. chick {$chickCost}, feed {$feedCost}, meds {$medCost} per bird; selling at KES {$priceKg}/kg at {$saleKg} kg each):\n";
    $line .= "• Total cost ≈ KES " . number_format($totalCost, 0) . "\n";
    $line .= "• Revenue ≈ KES " . number_format($revenue, 0) . "\n";
    $line .= "• **Net profit ≈ KES " . number_format($profit, 0) . "** (" . number_format($roi, 1) . "% ROI)\n";
    $line .= "• **Break-even ≈ KES " . number_format($breakeven, 0) . "/kg** — price must be above this to profit.";
    return ['answer' => $line, 'suggestions' => aiSuggestCalc()];
}

function aiCalcFCR(string $q): array
{
    $nums = aiNums($q);
    if (count($nums) < 2) {
        return ['answer' => 'FCR = kg feed eaten ÷ kg weight gained. Tell me both, e.g. "My FCR if I used 600 kg feed for 300 kg gain?"', 'suggestions' => aiSuggestCalc()];
    }
    $feed = $nums[0]; $gain = $nums[1];
    $fcr = $gain > 0 ? $feed / $gain : 0;
    $verdict = $fcr < 1.8 ? 'Excellent — very efficient.' : ($fcr < 2.2 ? 'Good for broilers.' : 'High — check feed quality, wastage and health.');
    return ['answer' => sprintf("FCR = %.1f kg feed ÷ %.1f kg gain = **%.2f**. %s (Broiler target ≈ 1.6–1.9; layers ≈ 2.0–2.2.)", $feed, $gain, $fcr, $verdict), 'suggestions' => ['How can I lower my FCR?', 'What is my break-even price for 200 broilers?']];
}

function aiCalcDose(string $q): array
{
    $nums = aiNums($q);
    if (count($nums) < 1) {
        return ['answer' => 'Medication dose = dose (mg/kg) × body weight (kg) ÷ concentration (mg/ml). Example: "How many ml for a 300 kg cow at 10 mg/kg with 100 mg/ml?"', 'suggestions' => aiSuggestCalc()];
    }
    $weight = $nums[0];
    $doseMg = $nums[1] ?? 10;
    $conc = $nums[2] ?? 100;
    $ml = $weight * $doseMg / max($conc, 0.001);
    return ['answer' => sprintf(
        "Dose ≈ %.0f kg × %d mg/kg ÷ %d mg/ml = **%.1f ml** injected/in-fed. ⚠️ Always confirm the drug label and withdraw periods — this is an estimate, not a prescription.",
        $weight, (int)$doseMg, (int)$conc, $ml
    ), 'suggestions' => ['What is the withdrawal period for common medicines?', 'Vaccination schedule for cattle?']];
}

function aiCalcMortality(string $q): array
{
    $nums = aiNums($q);
    if (count($nums) < 2) {
        return ['answer' => 'Mortality % = dead ÷ total × 100. Example: "Mortality rate if 12 died out of 500 birds?"', 'suggestions' => aiSuggestCalc()];
    }
    $dead = $nums[0]; $total = $nums[1];
    $pct = $total > 0 ? ($dead / $total) * 100 : 0;
    $verdict = $pct <= 2 ? 'Normal — within the healthy range.' : ($pct <= 5 ? 'Watch it — investigate feed, water and biosecurity.' : 'High — act now: check for disease, cull sick birds, review vaccination.');
    return ['answer' => sprintf("Mortality = %d ÷ %d × 100 = **%.1f%%**. %s (Target: under 2%% per week.)", (int)$dead, (int)$total, $pct, $verdict), 'suggestions' => ['Common poultry diseases and treatments?', 'Vaccination schedule for broilers?']];
}

function aiCalcWages(string $q): array
{
    $nums = aiNums($q);
    if (count($nums) < 3) {
        return ['answer' => 'Wage bill = workers × days × daily rate. Example: "Wages for 5 workers for 20 days at KES 600 each?"', 'suggestions' => aiSuggestCalc()];
    }
    $workers = $nums[0]; $days = $nums[1]; $rate = $nums[2];
    $total = $workers * $days * $rate;
    return ['answer' => sprintf("Wage bill = %d workers × %d days × KES %s = **KES %s** (%s per worker).", (int)$workers, (int)$days, number_format($rate, 0), number_format($total, 0), number_format($workers * $days * $rate / max($workers, 1), 0)), 'suggestions' => aiSuggestCalc()];
}

function aiCalcEggs(string $q): array
{
    $nums = aiNums($q);
    if (count($nums) < 2) {
        return ['answer' => 'Egg value = crates × price per crate (or eggs ÷ 30 × crate price). Example: "Value of 30 crates at KES 1,600 each?"', 'suggestions' => aiSuggestCalc()];
    }
    $qty = $nums[0]; $price = $nums[1];
    if (aiHasAny($q, ['egg']) && !aiHasAny($q, ['crate'])) {
        // "500 eggs at 55 per egg" → direct multiply; otherwise assume crate price
        if (str_contains($q, 'per egg')) {
            $total = $qty * $price;
            return ['answer' => sprintf("%d eggs × KES %s per egg = **KES %s** (≈ %.1f crates).", (int)$qty, number_format($price, 0), number_format($total, 0), $qty / 30), 'suggestions' => aiSuggestCalc()];
        }
        $crates = $qty / 30;
        $total = $crates * $price;
        return ['answer' => sprintf("≈ %d eggs ÷ 30 per crate = %.1f crates × KES %s = **KES %s**.", (int)$qty, $crates, number_format($price, 0), number_format($total, 0)), 'suggestions' => aiSuggestCalc()];
    }
    $total = $qty * $price;
    return ['answer' => sprintf("%d crates × KES %s = **KES %s** (≈ KES %s per egg).", (int)$qty, number_format($price, 0), number_format($total, 0), number_format($price / 30, 0)), 'suggestions' => aiSuggestCalc()];
}

function aiCalcMilk(string $q): array
{
    $nums = aiNums($q);
    if (count($nums) < 2) {
        return ['answer' => 'Milk value = litres × price per litre. Example: "Value of 40 litres at KES 50 each?"', 'suggestions' => aiSuggestCalc()];
    }
    $litres = $nums[0]; $price = $nums[1];
    $total = $litres * $price;
    $monthly = $total * 30;
    return ['answer' => sprintf("%.0f L × KES %s = **KES %s** (~KES %s if milked daily for a month).", $litres, number_format($price, 0), number_format($total, 0), number_format($monthly, 0)), 'suggestions' => aiSuggestCalc()];
}

function aiCalcMath(string $q): array
{
    // Normalize words: strip "what is" / "calculate" / "how much is" etc.
    $q = preg_replace('/^(what is|whats|what\'s|calculate|how much is|compute|equals|evaluate)[: ]*/i', '', $q);
    $q = preg_replace('/[?].*$/', '', $q); // drop trailing question
    // Extract the expression: a run of digits, operators, parens (may start with an open paren)
    if (!preg_match('/((?:\d|\([^)]*)[\d\s,\.\+\-\*\/%\^\(\)]*)/', $q, $m)) {
        return ['answer' => "I can do the math for you — try \"What is 30 * 1600?\" or \"Calculate (1500 + 400) * 50 / 1000\".", 'suggestions' => aiSuggestCalc()];
    }
    $expr = preg_replace('/[,\s]+/', '', $m[1]);
    if (!preg_match('/[\+\-\*\/%\^]/', $expr)) {
        return ['answer' => "I can do the math for you — try \"What is 30 * 1600?\" or \"Calculate (1500 + 400) * 50 / 1000\".", 'suggestions' => aiSuggestCalc()];
    }
    try {
        $result = aiMathEval($expr);
        return ['answer' => "**$expr = " . rtrim(rtrim(number_format($result, 6, '.', ''), '0'), '.') . "**", 'suggestions' => aiSuggestCalc()];
    } catch (Exception $e) {
        return ['answer' => "I couldn't evaluate that: " . $e->getMessage(), 'suggestions' => aiSuggestCalc()];
    }
}

/* ─────────────────────────────────────────────────────────────
 * 2b. LLM CONNECTOR — optional "Thinking" mode.
 * Supports: Groq (free, fast), Gemini, DeepSeek, Ollama (local).
 * When Thinking is ON, the LLM gets full farm context + can search the web.
 * ───────────────────────────────────────────────────────────── */
function aiLLM(PDO $pdo, string $q, string $provider = '', string $apiKey = '', string $model = ''): ?array
{
    $provider = $provider ?: (string)aiSetting($pdo, 'ai_provider');
    $apiKey   = $apiKey   ?: (string)aiSetting($pdo, 'ai_api_key');
    $model    = $model    ?: (string)aiSetting($pdo, 'ai_model');
    if ($apiKey === '' && $provider !== 'ollama' && $provider !== 'openrouter') return null;
    /* Default provider is OpenRouter (free Ox Alpha model). */
    if ($provider === '') $provider = 'openrouter';
    /* Model defaults per provider */
    $modelDefaults = [
        'groq'    => 'llama-3.3-70b-versatile',
        'gemini'  => 'gemini-flash-latest',
        'deepseek' => 'deepseek-chat',
        'ollama'  => 'llama3.1:8b',
    ];
    if ($model === '') $model = $modelDefaults[$provider] ?? 'llama-3.3-70b-versatile';

    $context = aiFarmContext($pdo);
    
    /* Check if the question needs web search */
    $webResults = '';
    if (aiNeedsWebSearch($q)) {
        $webResults = aiWebSearch($q);
    }
    
    $system = "You are Wangari, a smart farm assistant for a Kenyan farm. "
        . "You have FULL access to the farm's records and can search the internet for current information. "
        . "Answer in plain, practical English. Be helpful, give advice, and use the data below. "
        . "If asked about prices, weather, market conditions, or anything needing current info, use the web search results. "
        . "You can help with: farm planning, financial advice, veterinary guidance, market analysis, and any farming question.\n\n"
        . "FARM DATA:\n" . $context;
    if ($webResults !== '') {
        $system .= "\n\nWEB SEARCH RESULTS:\n" . $webResults;
    }

    try {
        /* OpenRouter — free Ox Alpha model */
        if ($provider === 'openrouter') {
            require_once dirname(__DIR__, 2) . '/Backend/config/openrouter.php';
            if (openrouter_is_configured()) {
                $res = aiHttpPost(OPENROUTER_API_URL, json_encode([
                    'model' => OPENROUTER_MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $q],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 1024,
                ]), [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENROUTER_API_KEY,
                    'HTTP-Referer: https://wangari.imeantech.com',
                    'X-Title: Wangari Farm AI',
                ]);
                if ($res && isset($res['choices'][0]['message']['content'])) {
                    return ['answer' => $res['choices'][0]['message']['content'], 'suggestions' => aiSmartSuggestions($pdo, $q)];
                }
            }
            return null;
        }
        
        /* Groq — free, fast, generous limits (14,400 req/day) */
        if ($provider === 'groq') {
            $res = aiHttpPost('https://api.groq.com/openai/v1/chat/completions', json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $q],
                ],
                'temperature' => 0.4,
                'max_tokens' => 1024,
            ]), ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
            if ($res && isset($res['choices'][0]['message']['content'])) {
                return ['answer' => $res['choices'][0]['message']['content'], 'suggestions' => aiSmartSuggestions($pdo, $q)];
            }
            return null;
        }
        
        /* Gemini */
        if ($provider === 'gemini') {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);
            $res = aiHttpPost($url, json_encode([
                'contents' => [['parts' => [['text' => $system . "\n\nFarmer asks: " . $q]]]],
                'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 1024],
            ]), ['Content-Type: application/json']);
            if ($res && isset($res['candidates'][0]['content']['parts'][0]['text'])) {
                return ['answer' => $res['candidates'][0]['content']['parts'][0]['text'], 'suggestions' => aiSmartSuggestions($pdo, $q)];
            }
            return null;
        }
        
        /* OpenAI-compatible (DeepSeek, Ollama, etc.) */
        $base = 'https://api.openai.com/v1';
        if ($provider === 'deepseek') $base = 'https://api.deepseek.com/v1';
        if ($provider === 'ollama')   $base = 'http://localhost:11434/v1';
        $res = aiHttpPost($base . '/chat/completions', json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $q],
            ],
            'temperature' => 0.4,
            'max_tokens' => 1024,
        ]), ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
        if ($res && isset($res['choices'][0]['message']['content'])) {
            return ['answer' => $res['choices'][0]['message']['content'], 'suggestions' => aiSmartSuggestions($pdo, $q)];
        }
    } catch (Exception $e) { /* fall through */ }
    return null;
}

/* ─────────────────────────────────────────────────────────────
 * 2c. WEB SEARCH — free DuckDuckGo search (no API key needed)
 * Returns formatted search results for the LLM context.
 * ───────────────────────────────────────────────────────────── */
function aiNeedsWebSearch(string $q): bool
{
    $q = strtolower($q);
    /* Search for things that need current/external info */
    $searchTriggers = ['price', 'market', 'weather', 'current', 'today', 'news', 'trend',
        'forecast', 'how much does', 'what is the cost', 'where to buy',
        'supplier', 'recipe', 'tutorial', 'how to make', 'advice', 'recommend',
        'best practice', 'latest', 'recent', 'online'];
    foreach ($searchTriggers as $t) {
        if (str_contains($q, $t)) return true;
    }
    return false;
}

function aiWebSearch(string $q): string
{
    /* DuckDuckGo instant answer API — free, no key */
    $url = 'https://api.duckduckgo.com/?q=' . urlencode($q) . '&format=json&no_html=1&skip_disambig=1';
    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) return '';
    $data = json_decode($json, true);
    if (!is_array($data)) return '';
    
    $results = [];
    
    /* Abstract (main answer) */
    if (!empty($data['AbstractText'])) {
        $results[] = "Summary: " . $data['AbstractText'];
    }
    
    /* Related topics */
    if (!empty($data['RelatedTopics'])) {
        foreach (array_slice($data['RelatedTopics'], 0, 5) as $topic) {
            if (isset($topic['Text'])) {
                $results[] = $topic['Text'];
            }
        }
    }
    
    /* Answer (direct answer if available) */
    if (!empty($data['Answer'])) {
        $results[] = "Answer: " . $data['Answer'];
    }
    
    return implode("\n", array_slice($results, 0, 5));
}

/** Generate smart follow-up suggestions based on context */
function aiSmartSuggestions(PDO $pdo, string $q): array
{
    $q = strtolower($q);
    /* Context-aware suggestions */
    if (str_contains($q, 'price') || str_contains($q, 'market')) {
        return ['What are current egg prices in Kenya?', 'How do I set profitable prices?', 'What is my break-even?'];
    }
    if (str_contains($q, 'weather') || str_contains($q, 'rain')) {
        return ['How does weather affect my crops?', 'What should I do before rains?', 'Weather forecast for this week?'];
    }
    if (str_contains($q, 'advice') || str_contains($q, 'recommend')) {
        return ['How can I increase my profits?', 'What are my biggest risks?', 'How to reduce costs?'];
    }
    /* Default */
    return ['How much did I sell this month?', 'What is coming up this week?', 'How can I improve my farm?'];
}

/** Read a setting from the settings table (safe). */
function aiSetting(PDO $pdo, string $key): string
{
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
        $stmt->execute([$key]);
        return (string)$stmt->fetchColumn();
    } catch (Exception $e) { return ''; }
}

/** Comprehensive farm summary — ALL modules for full AI context. */
function aiFarmContext(PDO $pdo): string
{
    $lines = [];
    $lines[] = '=== FINANCIAL SUMMARY ===';
    try {
        $sales = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN ("paid","completed")')->fetchColumn();
        $lines[] = 'All-time revenue: KES ' . number_format($sales, 0);
    } catch (Exception $e) {}
    try {
        $month = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE_FORMAT(created_at,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m") AND status IN ("paid","completed")')->fetchColumn();
        $lines[] = 'This month revenue: KES ' . number_format($month, 0);
    } catch (Exception $e) {}
    try {
        $exp = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(created_at,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m")')->fetchColumn();
        $lines[] = 'This month expenses: KES ' . number_format($exp, 0);
    } catch (Exception $e) {}
    try {
        $tot = 0; $rows = $pdo->query('SELECT balance, customer_name FROM customer_credits WHERE balance > 0')->fetchAll();
        foreach ($rows as $r) { $tot += (float)($r['balance'] ?? 0); }
        $lines[] = 'Outstanding credit: KES ' . number_format($tot, 0);
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = '=== LIVESTOCK ===';
    try {
        $f = (int)$pdo->query('SELECT COUNT(*) FROM flocks WHERE status="active"')->fetchColumn();
        $lines[] = "Active flocks: $f";
    } catch (Exception $e) {}
    try {
        $a = (int)$pdo->query('SELECT COUNT(*) FROM animals WHERE status IN ("alive","Active")')->fetchColumn();
        $lines[] = "Animals alive: $a";
    } catch (Exception $e) {}
    try {
        $byType = $pdo->query('SELECT type, COUNT(*) as c FROM animals WHERE status IN ("alive","Active") GROUP BY type ORDER BY c DESC')->fetchAll();
        if ($byType) $lines[] = 'By species: ' . implode(', ', array_map(fn($r) => $r['type'] . ' x' . $r['c'], $byType));
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = '=== PRODUCTION ===';
    try {
        $eggs = (float)$pdo->query('SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE DATE(record_date)=CURDATE()')->fetchColumn();
        $eggMonth = (float)$pdo->query('SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE DATE_FORMAT(record_date,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m")')->fetchColumn();
        $lines[] = "Eggs today: " . number_format($eggs, 0) . ", this month: " . number_format($eggMonth, 0);
    } catch (Exception $e) {}
    try {
        $milk = (float)$pdo->query('SELECT COALESCE(SUM(meat_weight_kg),0) FROM production_records WHERE DATE(record_date)=CURDATE()')->fetchColumn();
        if ($milk > 0) $lines[] = 'Production weight today: ' . number_format($milk, 1) . ' kg';
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = '=== INVENTORY ===';
    try {
        $alerts = (int)$pdo->query('SELECT COUNT(*) FROM system_alerts WHERE alert_type="low_stock" AND status="active"')->fetchColumn();
        $lines[] = "Low stock alerts: $alerts";
    } catch (Exception $e) {}
    try {
        $items = (int)$pdo->query('SELECT COUNT(*) FROM raw_materials')->fetchColumn();
        $lines[] = "Raw materials tracked: $items";
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = '=== CROPS ===';
    try {
        $fields = (int)$pdo->query('SELECT COUNT(*) FROM fields WHERE status="active"')->fetchColumn();
        $plantings = (int)$pdo->query('SELECT COUNT(*) FROM crop_plantings')->fetchColumn();
        $lines[] = "Active fields: $fields, Plantings: $plantings";
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = '=== STAFF ===';
    try {
        $w = (int)$pdo->query('SELECT COUNT(*) FROM workers WHERE status="active"')->fetchColumn();
        $lines[] = "Active workers: $w";
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = '=== UPCOMING ===';
    try {
        $week = (int)$pdo->query('SELECT COUNT(*) FROM reminders WHERE DATE(remind_at) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)')->fetchColumn();
        $lines[] = "Reminders this week: $week";
    } catch (Exception $e) {}
    
    $lines[] = '';
    $lines[] = 'Date: ' . date('Y-m-d H:i') . ' (Kenya time)';
    
    return implode("\n", $lines) ?: 'No farm data recorded yet.';
}

function aiHttpPost(string $url, string $body, array $headers): ?array
{
    if (!function_exists('curl_init')) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $out = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($out === false || $code >= 400) return null;
    $j = json_decode((string)$out, true);
    return is_array($j) ? $j : null;
}

/* ─────────────────────────────────────────────────────────────
 * 3. KNOWLEDGE BASE — seeded farming facts (ai_knowledge table)
 * ───────────────────────────────────────────────────────────── */
function aiKnowledge(PDO $pdo, string $q): ?array
{
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'ai_knowledge'")->fetchColumn();
        if (!$exists) return null;
    } catch (Exception $e) { return null; }

    // keyword → lookup query
    $map = [
        'vaccin' => ['cattle vaccination', 'vaccination schedule', 'vaccine'],
        'deworm' => ['deworming schedule', 'deworming', 'worm'],
        'newcastle' => ['newcastle disease', 'newcastle'],
        'fowl pox' => ['fowl pox', 'fowlpox'],
        'gumboro' => ['gumboro', 'infectious bursal'],
        'coccidiosis' => ['coccidiosis', 'coccidia'],
        'mastitis' => ['mastitis'],
        'foot and mouth' => ['foot and mouth', 'fmd', 'foot-and-mouth'],
        'anthrax' => ['anthrax', 'blackleg'],
        'mange' => ['mange', 'skin mites'],
        'east coast fever' => ['east coast fever', 'ecf', 'theileriosis'],
        'newcastle' => ['newcastle', 'nd'],
        'african swine' => ['african swine', 'asf'],
        'feed' => ['feeding rate', 'feed requirement', 'feeding'],
        'biosecurity' => ['biosecurity', 'disinfect', 'quarantine'],
        'withdraw' => ['withdrawal period', 'withdraw'],
        'egg' => ['egg production', 'egg laying', 'layers'],
    ];

    $topic = null;
    foreach ($map as $key => $needles) {
        foreach ($needles as $n) {
            if (str_contains($q, $n)) { $topic = $key; break 2; }
        }
    }
    if (!$topic) return null;

    $stmt = $pdo->prepare('SELECT title, content FROM ai_knowledge WHERE topic=? LIMIT 1');
    $stmt->execute([$topic]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    return [
        'answer' => "**" . $row['title'] . "**\n\n" . $row['content'],
        'suggestions' => ['What is the break-even price for 200 broilers?', 'How much feed do 50 broilers need?'],
    ];
}

/**
 * Seed the knowledge base (called from auto_migrate-safe path). Idempotent.
 */
function aiSeedKnowledge(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_knowledge (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topic VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    } catch (Exception $e) { return; }

    $rows = [
        ['vaccin', 'Cattle vaccination schedule (Kenya)', "Core vaccines for cattle in Kenya:\n• FMD (Foot & Mouth): every 6 months (Jan & Jul) — mandatory in many counties.\n• Anthrax & Blackquarter: annually, 2 weeks before rains.\n• LSD (Lumpy Skin Disease): annually in high-risk areas.\n• Brucellosis (S19 calves): once at 4–8 months.\n• Rabies: annually (dogs, but cattle too if exposed).\nAlways vaccinate healthy animals only; keep records of batch numbers."],
        ['deworm', 'Deworming schedule', "• Cattle: every 3 months (or strategic: start of rains + mid-rains) with a broad-spectrum anthelmintic.\n• Sheep & goats: every 3 months; kids/lambs from 6–8 weeks.\n• Poultry: not typical; manage litter and use coccidiostats if needed.\n• Pigs: every 3 months.\nRotate drug families to slow resistance; dose by weight, not by eye."],
        ['newcastle', 'Newcastle Disease (NCD)', "Viral, highly contagious in poultry — spreads fast, high death rate.\nSymptoms: greenish watery diarrhoea, twisting neck, gasping, sudden deaths.\nPrevention: vaccine (La Sota/Komarov) at day 1 (or 7), booster at 3 weeks, then every 3 months.\nAction: isolate sick birds, disinfect, dispose of dead birds safely (bury/burn)."],
        ['fowl pox', 'Fowl Pox', "Viral disease of chickens: wart-like scabs on comb, wattles and face (dry form) or lesions in mouth (wet form).\nLower mortality but cuts production.\nPrevention: vaccine at 8–16 weeks (wing-web method), annual booster.\nAction: cull badly affected birds; improve biosecurity."],
        ['gumboro', 'Gumboro (Infectious Bursal Disease)', "Viral disease of young chickens (3–6 weeks): watery diarrhoea, ruffled feathers, pecking at vent, sudden death spikes.\nPrevention: maternal immunity + live vaccine at 10–14 days, booster at 3–4 weeks.\nAction: strict disinfection (very hardy virus); report to vet."],
        ['coccidiosis', 'Coccidiosis', "Parasitic gut disease in poultry: blood-stained droppings, pale combs, drooping wings, poor growth.\nTriggers: damp litter, overcrowding.\nPrevention: keep litter dry, use coccidiostat in feed (starter), good ventilation.\nTreatment: amprolium in water per label; clean and dry the house."],
        ['mastitis', 'Mastitis (udder infection)', "Inflammation of the udder in dairy cows: swollen/hard udder, flakes or clots in milk, drop in yield.\nCauses: dirty bedding, poor milking hygiene, injury.\nPrevention: clean teats before/after milking, teat dipping, dry-cow therapy.\nAction: treat with antibiotics per vet advice; withdraw milk for the full withdrawal period."],
        ['foot and mouth', 'Foot & Mouth Disease (FMD)', "Highly contagious viral disease of cattle, sheep, goats, pigs: fever, blisters in mouth and on feet, drooling, lameness, drop in milk.\nPrevention: annual/bi-annual vaccination; quarantine new animals.\nAction: report to the vet/DVS immediately — FMD is a notifiable disease in Kenya."],
        ['anthrax', 'Anthrax / Blackquarter', "Bacterial, deadly: sudden death with blood from nose/anus (anthrax) or swollen hot muscle areas (blackquarter).\nPrevention: annual vaccination before rains.\nAction: DO NOT open the carcass (spores spread). Bury deep with quicklime or burn. Report immediately."],
        ['mange', 'Mange (skin mites)', "Mites cause intense itching, hair loss, scaly/red skin on cattle, sheep, goats, dogs.\nTreatment: injectable or pour-on acaricide (e.g. ivermectin) per weight; repeat after 10–14 days.\nPrevention: keep pens dry, treat new animals on arrival."],
        ['east coast fever', 'East Coast Fever (ECF)', "Tick-borne disease of cattle (Theileria parva) — the #1 killer of cattle in Kenya.\nSymptoms: fever, swollen lymph nodes (ear, shoulder), breathing trouble, watering eyes.\nPrevention: tick control (dipping/spraying every 1–2 weeks), vaccination (Muguga cocktail) where available.\nAction: treat early with buparvaquone — late treatment often fails."],
        ['african swine', 'African Swine Fever (ASF)', "Viral, usually fatal in pigs: high fever, red/purple skin patches, vomiting, sudden death.\nNo vaccine or cure — biosecurity is everything.\nAction: report immediately; quarantine; dispose of carcasses safely."],
        ['biosecurity', 'Basic biosecurity (all farms)', "• Keep the farm fenced; control visitors and vehicles.\n• Footbaths with disinfectant at house/shed entrances.\n• Quarantine new animals 2–3 weeks before mixing.\n• Clean and disinfect houses between batches (all-in/all-out).\n• Control rodents, birds and flies; store feed off the ground.\n• Dispose of dead stock by burying deep or burning."],
        ['withdraw', 'Withdrawal periods (why they matter)', "Withdrawal period = time after the last dose before meat/milk/eggs are safe to sell.\n• Common antibiotics: 5–28 days (check label).\n• Milk withdrawal for treated cows: usually 72h–7 days.\n• Eggs: most medicines 0–14 days — read the label.\nNever sell produce before withdrawal is complete — it's a health risk and can get your stock rejected."],
        ['egg', 'Boosting egg production (layers)', "• Light: 14–16 hours of light per day once laying starts.\n• Feed: 110–120 g layer mash per bird per day; clean water always.\n• Calcium: oyster shell / limestone for shell strength.\n• Collect eggs 2–3× daily; store pointy-end-down in a cool room.\n• Watch for stress (moving, noise, hunger) — it drops production fast."],
        ['feed', 'Feeding rates (rough guide)', "Per animal per day (dry matter / feed):\n• Dairy cow: 10–15 kg roughage + 2–4 kg concentrate (scale to milk yield).\n• Beef steer: 8–10 kg.\n• Sheep/goat: 1.5–2 kg.\n• Broiler: starter 0–2wk ~35 g/bird/day, finisher 5–6wk ~140 g.\n• Layer: 110–120 g/bird/day.\n• Pig: 2–3 kg grower.\nAdjust by weight and condition — weigh periodically."],
    ];
    $stmt = $pdo->prepare('INSERT INTO ai_knowledge (topic, title, content) VALUES (?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content)');
    foreach ($rows as $r) {
        try { $stmt->execute($r); } catch (Exception $e) { /* ignore */ }
    }
}
