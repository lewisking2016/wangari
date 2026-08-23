<?php
/**
 * Paystack Configuration
 * API keys and settings for payment integration
 */
declare(strict_types=1);

// Test Mode Keys (replace with live keys for production)
define('PAYSTACK_SECRET_KEY', 'sk_test_2652161657c91aa5646abbbc2d4c6ee74eb7361c');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_8e025947d47366ea56c49264544b249cc70f36cf');

// Base URLs
define('PAYSTACK_API_BASE', 'https://api.paystack.co');

// Plan IDs (create these in Paystack dashboard)
define('PAYSTACK_PLANS', [
    'pro_monthly' => '',  // Will be created automatically
    'pro_annual' => '',
    'plus_monthly' => '',
    'plus_annual' => '',
]);

// Webhook secret (set this in Paystack dashboard)
define('PAYSTACK_WEBHOOK_SECRET', '');

// Currency
define('PAYSTACK_CURRENCY', 'KES');

/**
 * Make a Paystack API request
 */
function paystackRequest(string $endpoint, string $method = 'GET', array $data = []): array
{
    $url = PAYSTACK_API_BASE . $endpoint;
    
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
        'Cache-Control: no-cache',
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    
    return [
        'status' => $decoded['status'] ?? false,
        'message' => $decoded['message'] ?? '',
        'data' => $decoded['data'] ?? [],
        'http_code' => $httpCode,
    ];
}

/**
 * Initialize a Paystack transaction
 */
function paystackInitializeTransaction(array $params): array
{
    return paystackRequest('/transaction/initialize', 'POST', $params);
}

/**
 * Verify a Paystack transaction
 */
function paystackVerifyTransaction(string $reference): array
{
    return paystackRequest("/transaction/verify/{$reference}");
}

/**
 * Create a Paystack customer
 */
function paystackCreateCustomer(array $params): array
{
    return paystackRequest('/customer', 'POST', $params);
}

/**
 * Create a Paystack plan
 */
function paystackCreatePlan(array $params): array
{
    return paystackRequest('/plan', 'POST', $params);
}

/**
 * Create a Paystack subscription
 */
function paystackCreateSubscription(array $params): array
{
    return paystackRequest('/subscription', 'POST', $params);
}

/**
 * Disable a Paystack subscription
 */
function paystackDisableSubscription(string $subscriptionCode, string $email): array
{
    return paystackRequest('/subscription/disable', 'POST', [
        'code' => $subscriptionCode,
        'email' => $email,
        'token' => bin2hex(random_bytes(16)),
    ]);
}

/**
 * List Paystack plans
 */
function paystackListPlans(): array
{
    return paystackRequest('/plan');
}
