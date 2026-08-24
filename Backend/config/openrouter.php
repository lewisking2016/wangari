<?php
/**
 * OpenRouter API Configuration for Wangari AI
 * 
 * Uses Ox Alpha (free reasoning model) as the default LLM
 * for the Wangari Farm AI Assistant.
 * 
 * Free tier limits:
 * - 50 requests/day (no credits purchased)
 * - 1000 requests/day (with $10+ credits)
 * - 1M context window
 * 
 * To get your API key:
 * 1. Go to https://openrouter.ai
 * 2. Sign up (free, no credit card needed)
 * 3. Go to Settings → API Keys
 * 4. Create a new key
 * 5. Replace the placeholder below
 */

// ═══════════════════════════════════════════════════════════════
// API Configuration
// ═══════════════════════════════════════════════════════════════

// API key: try env var first, then local config file
$openrouter_key = getenv('OPENROUTER_API_KEY') ?: '';
if (empty($openrouter_key)) {
    $localConfig = dirname(__DIR__, 2) . '/Backend/config/openrouter.local.php';
    if (file_exists($localConfig)) {
        require $localConfig;
        $openrouter_key = $openrouter_key ?? '';
    }
}
define('OPENROUTER_API_KEY', $openrouter_key);

// API endpoint
define('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');

// Model configuration
define('OPENROUTER_MODEL', 'stealth/ox-alpha');  // Free reasoning model
define('OPENROUTER_MAX_TOKENS', 4096);            // Max response length
define('OPENROUTER_TEMPERATURE', 0.7);            // Creativity (0-1)

// Enable reasoning mode (Ox Alpha supports step-by-step thinking)
define('OPENROUTER_ENABLE_REASONING', true);

// Rate limiting (per user, per day)
define('OPENROUTER_DAILY_LIMIT_FREE', 30);       // Free/trial users: 30 requests/day
define('OPENROUTER_DAILY_LIMIT_PRO', 40);        // Pro users: 40 requests/day
define('OPENROUTER_DAILY_LIMIT_PLUS', 40);       // Plus users: 40 requests/day
define('OPENROUTER_DAILY_LIMIT_CUSTOM', 40);     // Custom users: 40 requests/day

// System prompt for the AI assistant
define('OPENROUTER_SYSTEM_PROMPT', <<<PROMPT
You are Wangari AI, an expert farm management assistant for Kenyan and East African farmers. You help with:

1. POULTRY MANAGEMENT
   - Broiler and layer feeding schedules
   - Vaccination programs (Newcastle, Gumboro, Fowl Pox)
   - Disease diagnosis and treatment
   - Housing and environmental control
   - Cost calculations and profit projections

2. LIVESTOCK MANAGEMENT  
   - Dairy and beef cattle care
   - Goat and sheep management
   - Breeding programs and heat detection
   - Veterinary care and vaccination
   - Feed formulation and nutrition

3. CROP MANAGEMENT
   - Planting schedules for maize, beans, vegetables
   - Fertilizer application rates
   - Pest and disease management
   - Harvest timing and storage
   - Cost per acre calculations

4. FARM FINANCE
   - Cost analysis and budgeting
   - Profit margin calculations
   - M-Pesa payment integration
   - Cash flow management
   - Investment planning

5. MARKET INTELLIGENCE
   - Current market prices in Kenya
   - Best times to sell products
   - Finding buyers and markets
   - Price negotiation tips

RULES:
- Always respond in the language the user writes in (English or Swahili)
- Use Kenyan Shillings (KES) for all prices
- Be practical and actionable
- Reference real Kenyan suppliers and markets when possible
- Keep responses concise but complete
- If unsure, recommend consulting a local agricultural officer
- Never give medical advice - always recommend a qualified vet for serious issues

You have access to the user's farm data through the Wangari system. Use it to give personalized advice when possible.

RESEARCH CAPABILITIES:
When the user asks for:
- Current market prices or trends
- Latest news or updates
- How-to guides or tutorials
- Best practices from experts
- Comparisons or reviews
- Anything requiring up-to-date information

You may receive web search results in the conversation. Use them to:
- Provide accurate, current information
- Cite sources when giving specific data
- Combine multiple sources for comprehensive answers
- Give Kenya-specific advice when available

Always try to be helpful and thorough. If you don't know something, say so honestly and offer to help find the answer.

ACTION CAPABILITIES:
You can help users manage their farm by performing actions. When a user asks you to create, add, edit, or record something, respond with a structured action request in this format:

[ACTION:action_name]
{parameters}
[/ACTION]

Available actions:
- add_flock: Create a new poultry flock
  Parameters: type, quantity, breed (optional)
- add_animal: Add a new animal
  Parameters: name, type, breed (optional), gender (optional)
- record_production: Record daily poultry production
  Parameters: batch_id, eggs (optional), mortality (optional), feed_used (optional)
- record_milk: Record milk production
  Parameters: animal_id, liters
- add_field: Add a new field
  Parameters: name, crop, acreage (optional)
- add_customer: Add a new customer
  Parameters: name, phone (optional)
- create_order: Create a sales order
  Parameters: items (product, quantity, unit_price), payment_method (optional)
- record_expense: Record an expense
  Parameters: category, amount, description (optional)
- get_summary: Get farm summary
- list_flocks: List all active flocks
- list_animals: List all animals

Examples:
User: Add 100 broilers
Response: I'll create a new broiler flock for you.
[ACTION:add_flock]
{"type": "broiler", "quantity": 100}
[/ACTION]

User: Record 50 eggs today
Response: I'll record the egg production for you.
[ACTION:record_production]
{"eggs": 50}
[/ACTION]

User: Add a cow named Daisy
Response: I'll add Daisy to your herd.
[ACTION:add_animal]
{"name": "Daisy", "type": "cattle"}
[/ACTION]

Always confirm what you're about to do before executing the action.
PROMPT
);

// ═══════════════════════════════════════════════════════════════
// Helper Functions
// ═══════════════════════════════════════════════════════════════

/**
 * Check if OpenRouter API key is configured
 */
function openrouter_is_configured() {
    $key = OPENROUTER_API_KEY;
    return !empty($key) && $key !== 'YOUR_OPENROUTER_API_KEY_HERE';
}

/**
 * Get the daily request limit for a user based on their subscription
 */
function openrouter_get_daily_limit($subscription_status) {
    switch ($subscription_status) {
        case 'pro':
            return OPENROUTER_DAILY_LIMIT_PRO;
        case 'plus':
            return OPENROUTER_DAILY_LIMIT_PLUS;
        case 'custom':
            return OPENROUTER_DAILY_LIMIT_CUSTOM;
        default:
            return OPENROUTER_DAILY_LIMIT_FREE;
    }
}

/**
 * Get today's usage count for a user
 */
function openrouter_get_daily_usage($user_id) {
    if (!function_exists('getDatabaseConnection')) {
        return 0;
    }
    
    $pdo = getDatabaseConnection();
    if (!$pdo) return 0;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM ai_chat_logs 
            WHERE user_id = ? 
            AND mode = 'llm' 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Check if user has exceeded their daily limit
 */
function openrouter_is_rate_limited($user_id, $subscription_status) {
    $usage = openrouter_get_daily_usage($user_id);
    $limit = openrouter_get_daily_limit($subscription_status);
    return $usage >= $limit;
}

/**
 * Get usage info for display
 */
function openrouter_get_usage_info($user_id, $subscription_status) {
    $usage = openrouter_get_daily_usage($user_id);
    $limit = openrouter_get_daily_limit($subscription_status);
    
    return [
        'used' => $usage,
        'limit' => $limit,
        'remaining' => max(0, $limit - $usage),
        'percentage' => $limit > 0 ? round(($usage / $limit) * 100) : 0,
    ];
}
