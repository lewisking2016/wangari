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

// Initialize AI
$ai = new FarmAI();

// Process message
$response = $ai->processMessage($message);

// Return response
echo json_encode([
    'success' => true,
    'response' => $response,
    'timestamp' => date('Y-m-d H:i:s')
]);
