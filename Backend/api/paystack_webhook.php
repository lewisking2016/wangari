<?php
/**
 * Paystack Webhook Handler
 * Handles payment events from Paystack
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paystack.php';

$pdo = getDatabaseConnection();

// Get the webhook signature
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Read the raw body
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

// Verify webhook signature (if secret is set)
if (PAYSTACK_WEBHOOK_SECRET && $signature) {
    $expectedSignature = hash_hmac('sha512', $rawBody, PAYSTACK_WEBHOOK_SECRET);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Process the event
$event = $payload['event'] ?? '';
$data = $payload['data'] ?? [];

try {
    switch ($event) {
        
        // ═══ CHARGE SUCCESS ═══
        case 'charge.success':
            $reference = $data['reference'] ?? '';
            $amount = $data['amount'] ?? 0;
            $metadata = $data['metadata'] ?? [];
            $userId = (int)($metadata['user_id'] ?? 0);
            $plan = $metadata['plan'] ?? '';
            $billing = $metadata['billing'] ?? 'monthly';
            
            if ($userId && $reference) {
                // Check if already processed
                $stmt = $pdo->prepare('SELECT id FROM platform_revenue WHERE mpesa_receipt = ?');
                $stmt->execute([$reference]);
                if (!$stmt->fetch()) {
                    // Calculate subscription end date
                    $endDate = $billing === 'annual' 
                        ? date('Y-m-d', strtotime('+1 year'))
                        : date('Y-m-d', strtotime('+30 days'));
                    
                    // Update user subscription
                    $pdo->prepare('UPDATE platform_users SET subscription_status = "active", subscription_expires = ? WHERE id = ?')
                        ->execute([$endDate, $userId]);
                    
                    // Record revenue
                    $pdo->prepare('INSERT INTO platform_revenue (user_id, amount, type, payment_method, mpesa_receipt, description, recorded_by) VALUES (?, ?, "subscription", "paystack", ?, ?, 1)')
                        ->execute([$userId, $amount / 100, $reference, "Subscription: $plan ($billing)"]);
                    
                    // Log activity
                    $pdo->prepare('INSERT INTO platform_activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES (1, "payment_received", "user", ?, ?, ?)')
                        ->execute([$userId, "Received KES " . ($amount / 100) . " for $plan subscription", 'webhook']);
                }
            }
            break;
        
        // ═══ SUBSCRIPTION CREATED ═══
        case 'subscription.create':
            $subscriptionCode = $data['subscription_code'] ?? '';
            $email = $data['email'] ?? '';
            
            // Store subscription code for future reference
            if ($subscriptionCode && $email) {
                $pdo->prepare('UPDATE platform_users SET paystack_subscription_code = ? WHERE email = ?')
                    ->execute([$subscriptionCode, $email]);
            }
            break;
        
        // ═══ SUBSCRIPTION DISABLED ═══
        case 'subscription.disable':
            $subscriptionCode = $data['subscription_code'] ?? '';
            $email = $data['email'] ?? '';
            
            if ($email) {
                // Downgrade user to expired
                $pdo->prepare('UPDATE platform_users SET subscription_status = "expired" WHERE email = ?')
                    ->execute([$email]);
            }
            break;
        
        // ═══ INVOICE PAYMENT ═══
        case 'invoice.payment_failed':
            $email = $data['customer']['email'] ?? '';
            
            if ($email) {
                // Mark subscription as past due
                $pdo->prepare('UPDATE platform_users SET subscription_status = "past_due" WHERE email = ?')
                    ->execute([$email]);
            }
            break;
        
        default:
            // Log unhandled events
            error_log("Paystack webhook: Unhandled event: $event");
    }
    
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Paystack webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
}
