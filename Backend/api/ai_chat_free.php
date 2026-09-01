<?php
/**
 * Wangari AI Chat — Free Self-Hosted Version
 * 
 * Uses Ollama + Mistral/Llama running locally on the VPS.
 * Zero API costs. Data stays on YOUR server.
 * 
 * Supports: English + Swahili
 * Features: Farm data queries, pest advice, market prices, general farming Q&A
 * 
 * Endpoint: POST /Backend/api/ai_chat_free.php
 * Body: { "user_id": 123, "message": "Why are my layers losing eggs?" }
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$user_id = (int)($input['user_id'] ?? 0);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'message required']);
    exit;
}

// Get user's farm data for context
$farmData = getFarmContext($pdo, $user_id);

// Build the prompt with farm context
$prompt = buildPrompt($message, $farmData);

// Call Ollama (self-hosted, FREE)
$response = callOllama($prompt);

echo json_encode([
    'reply' => $response,
    'model' => 'mistral-small3.1',
    'cost' => '$0.00',
    'farm_context' => [
        'eggs_today' => $farmData['eggs_today'] ?? 0,
        'mortality_today' => $farmData['mortality_today'] ?? 0,
        'profit_month' => $farmData['profit_month'] ?? 0
    ]
]);

// ═══════════════════════════════════════════════════════════════
// FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getFarmContext(PDO $pdo, int $user_id): array {
    $today = date('Y-m-d');
    $month_start = date('Y-m-01');
    
    $context = [];
    
    // Today's production
    $stmt = $pdo->prepare("SELECT eggs_collected, mortality, milk_litres FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
    $stmt->execute([$user_id, $today]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    $context['eggs_today'] = $prod['eggs_collected'] ?? 0;
    $context['mortality_today'] = $prod['mortality'] ?? 0;
    $context['milk_today'] = $prod['milk_litres'] ?? 0;
    
    // This month's totals
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) as eggs, COALESCE(SUM(mortality), 0) as mortality FROM daily_production WHERE user_id = ? AND record_date >= ?");
    $stmt->execute([$user_id, $month_start]);
    $month_prod = $stmt->fetch(PDO::FETCH_ASSOC);
    $context['eggs_month'] = $month_prod['eggs'] ?? 0;
    $context['mortality_month'] = $month_prod['mortality'] ?? 0;
    
    // This month's financials
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = ? AND income_date >= ?");
    $stmt->execute([$user_id, $month_start]);
    $context['income_month'] = (float) $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date >= ?");
    $stmt->execute([$user_id, $month_start]);
    $context['expenses_month'] = (float) $stmt->fetchColumn();
    
    $context['profit_month'] = $context['income_month'] - $context['expenses_month'];
    
    // Feed stock
    $stmt = $pdo->prepare("SELECT quantity FROM simple_inventory WHERE user_id = ? AND item_name LIKE '%feed%' LIMIT 1");
    $stmt->execute([$user_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    $context['feed_stock'] = $inv ? (int) $inv['quantity'] : 0;
    
    return $context;
}

function buildPrompt(string $message, array $farmData): string {
    $system = <<<EOT
You are Wangari, a friendly and knowledgeable Kenyan farm management AI assistant. 
You help smallholder farmers in Kenya manage their poultry, livestock, and crops.

RULES:
1. Answer in simple, clear English (or Swahili if the user writes in Swahili)
2. Be practical and specific — give actionable advice
3. Reference the farmer's actual data when available
4. Keep answers concise (2-4 sentences max for simple questions)
5. For serious problems (disease, high mortality), always recommend contacting a vet
6. Never recommend dangerous chemicals or treatments without proper warnings
7. Use KES (Kenyan Shillings) for all monetary references
8. Be warm and encouraging — farming is hard work

KENYA-SPECIFIC KNOWLEDGE:
- Layers: Peak production 80-90% at 24-40 weeks. FCR 1.8-2.2. Cost per bird KES 380-500/month
- Broilers: Market weight 2kg in 35-42 days. FCR 1.5-1.8. Cost per kg KES 270-350
- Common diseases: Newcastle, Gumboro, Fowl Pox, Coccidiosis, Marek's
- Vaccination schedule: Marek's (day 1), NDV (day 7), IB (day 7), Gumboro (day 14)
- Feed: Layers mash KES 4,500-5,500/bag. Broiler finisher KES 4,000-5,000/bag
- Egg prices: Wholesale KES 380-450/crate. Retail KES 450-550/crate
- Weather: Rainy seasons Mar-May, Oct-Dec. Dry seasons Jun-Sep, Jan-Feb

FARMER'S CURRENT DATA:
- Eggs today: {$farmData['eggs_today'] ?? 'No data'}
- Mortality today: {$farmData['mortality_today'] ?? 'No data'}
- Eggs this month: {$farmData['eggs_month'] ?? 'No data'}
- Mortality this month: {$farmData['mortality_month'] ?? 'No data'}
- Profit this month: KES " . number_format($farmData['profit_month'] ?? 0) . "
- Feed stock: {$farmData['feed_stock'] ?? 'Unknown'} bags
EOT;

    return $system . "\n\nFarmer asks: " . $message . "\n\nWangari answers:";
}

function callOllama(string $prompt): string {
    $ollama_url = 'http://localhost:11434/api/generate';
    
    $payload = json_encode([
        'model' => 'mistral-small3.1',  // Or 'llama3.2' for lighter model
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'num_predict' => 300,  // Keep responses short
        ]
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $payload,
            'timeout' => 30  // 30 second timeout
        ]
    ]);
    
    $response = @file_get_contents($ollama_url, false, $context);
    
    if ($response === false) {
        // Ollama not running — fall back to rule-based responses
        return fallbackResponse($prompt);
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['response'])) {
        return trim($result['response']);
    }
    
    return fallbackResponse($prompt);
}

function fallbackResponse(string $prompt): string {
    $lower = strtolower($prompt);
    
    // Simple rule-based fallback when Ollama is not available
    if (strpos($lower, 'mortality') !== false || strpos($lower, 'death') !== false) {
        return "⚠️ High mortality can be caused by: 1) Disease (check vaccination schedule), 2) Poor ventilation, 3) Contaminated water, 4) Overcrowding. If more than 2% monthly, contact a vet immediately.";
    }
    
    if (strpos($lower, 'egg') !== false && (strpos($lower, 'low') !== false || strpos($lower, 'drop') !== false || strpos($lower, 'few') !== false)) {
        return "📉 Low egg production can be caused by: 1) Heat stress (provide shade + water), 2) Poor feed quality, 3) Disease (check for Newcastle), 4) Lighting (layers need 14-16 hours of light). Check your FCR and mortality rates.";
    }
    
    if (strpos($lower, 'feed') !== false) {
        return "📦 Feed management tips: 1) Store in cool, dry place, 2) Use within 3 weeks of purchase, 3) Never mix old and new feed, 4) Clean feeders daily to prevent contamination. Current cost benchmark: KES 350-500/bag.";
    }
    
    if (strpos($lower, 'profit') !== false || strpos($lower, 'money') !== false) {
        return "💰 To calculate your profit: Revenue - Costs = Profit. Use Wangari's Finance Hub for automatic calculations. A good layer farm should profit KES 50-100/bird/month after all costs.";
    }
    
    if (strpos($lower, 'vaccin') !== false) {
        return "💉 Key vaccinations for layers: Day 1 (Marek's), Week 1 (NDV + IB), Week 2 (Gumboro), Week 4 (Fowl Pox), Week 5 (ND booster), Week 8 (ND + IB final). Never skip Gumboro!";
    }
    
    if (strpos($lower, 'swahili') !== false || preg_match('/\b(ni|ya|wa|kwa|na)\b/', $lower)) {
        return "Habari! Mimi ni Wangari, msaidizi wako wa kilimo. Ninaweza kukusaidia na: matunda ya kuku, chakula, magonjwa, chanjo, na hesabu ya faida. Niulize chochote!";
    }
    
    return "Thank you for your question! I can help with: egg production, feed management, mortality, vaccinations, profit calculations, and more. Please ask a specific question about your farm.";
}
