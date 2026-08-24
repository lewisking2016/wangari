<?php
/**
 * AI Chat API Endpoint
 * 
 * Handles incoming chat messages and returns AI responses
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/role_permissions.php';
wangariStartSession();

if (empty($_SESSION['user_id']) || !wangariIsFarmSystemRole((string)($_SESSION['role'] ?? ''))) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit();
}

$message = $input['message'];
$lang = $input['lang'] ?? 'en';

// Load the AI engine
require_once __DIR__ . '/../config/farm_ai.php';
require_once __DIR__ . '/../config/openrouter.php';

// Initialize AI
$ai = new FarmAI();

// Process message with timing
$startTime = microtime(true);
$response = $ai->processMessage($message);
$responseTime = round((microtime(true) - $startTime) * 1000); // ms

// Get usage info
$userId = $_SESSION['user_id'] ?? 0;
$subStatus = $_SESSION['subscription_status'] ?? 'trial';
$usageInfo = openrouter_get_usage_info($userId, $subStatus);

// Detect mode from response
$mode = 'local';
$model = '';
if (strpos($response, 'AI Powered') !== false || strpos($response, 'AI-powered') !== false) {
    $mode = 'llm';
    $model = 'Ox Alpha';
}

// Return response with metadata
echo json_encode([
    'success' => true,
    'response' => $response,
    'timestamp' => date('Y-m-d H:i:s'),
    'metadata' => [
        'response_time_ms' => $responseTime,
        'mode' => $mode,
        'model' => $model,
        'tokens_used' => $usageInfo['used'],
        'tokens_limit' => $usageInfo['limit'],
        'tokens_remaining' => $usageInfo['remaining'],
    ]
]);
