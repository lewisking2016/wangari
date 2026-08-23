<?php
/**
 * Paystack Payment API
 * Handles payment initialization and verification
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/paystack.php';

wangariStartSession();

$pdo = getDatabaseConnection();
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

try {
    switch ($action) {
        
        // ═══ INITIALIZE PAYMENT ═══
        case 'initialize':
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $plan = $input['plan'] ?? '';
            $billing = $input['billing'] ?? 'monthly'; // monthly or annual
            
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Please login to subscribe']);
                exit;
            }
            
            if (!in_array($plan, ['pro', 'plus'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid plan selected']);
                exit;
            }
            
            // Get user email
            $stmt = $pdo->prepare('SELECT email, full_name, username FROM platform_users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            
            // Calculate amount based on plan and billing
            $amounts = [
                'pro' => ['monthly' => 150000, 'annual' => 1500000], // in kobo/cents
                'plus' => ['monthly' => 450000, 'annual' => 4500000],
            ];
            
            $amount = $amounts[$plan][$billing] ?? 0;
            
            if ($amount === 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid plan or billing cycle']);
                exit;
            }
            
            // Generate unique reference
            $reference = 'WGR-' . strtoupper(substr(uniqid(), -8)) . '-' . bin2hex(random_bytes(4));
            
            // Store pending subscription
            $stmt = $pdo->prepare('INSERT INTO pending_subscriptions (user_id, plan, billing, amount, reference, status, created_at) VALUES (?, ?, ?, ?, ?, "pending", NOW()) ON DUPLICATE KEY UPDATE plan=VALUES(plan), billing=VALUES(billing), amount=VALUES(amount), reference=VALUES(reference), updated_at=NOW()');
            $stmt->execute([$userId, $plan, $billing, $amount / 100, $reference]);
            
            // Initialize Paystack transaction
            $result = paystackInitializeTransaction([
                'email' => $user['email'],
                'amount' => $amount,
                'reference' => $reference,
                'currency' => PAYSTACK_CURRENCY,
                'metadata' => [
                    'user_id' => $userId,
                    'plan' => $plan,
                    'billing' => $billing,
                    'custom_fields' => [
                        [
                            'display_name' => 'Plan',
                            'variable_name' => 'plan',
                            'value' => strtoupper($plan) . ' (' . $billing . ')',
                        ],
                    ],
                ],
                'callback_url' => 'https://wangari.imeantech.com/Frontend/pages/payment_callback.php',
            ]);
            
            if ($result['status']) {
                echo json_encode([
                    'success' => true,
                    'authorization_url' => $result['data']['authorization_url'] ?? '',
                    'reference' => $reference,
                    'access_code' => $result['data']['access_code'] ?? '',
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['message'] ?? 'Failed to initialize payment',
                ]);
            }
            break;
        
        // ═══ VERIFY PAYMENT ═══
        case 'verify':
            $reference = $input['reference'] ?? $_GET['reference'] ?? '';
            
            if (!$reference) {
                http_response_code(400);
                echo json_encode(['error' => 'Reference required']);
                exit;
            }
            
            $result = paystackVerifyTransaction($reference);
            
            if ($result['status'] && $result['data']['status'] === 'success') {
                $data = $result['data'];
                $metadata = $data['metadata'] ?? [];
                $userId = (int)($metadata['user_id'] ?? 0);
                $plan = $metadata['plan'] ?? '';
                $billing = $metadata['billing'] ?? 'monthly';
                
                // Calculate subscription end date
                $endDate = $billing === 'annual' 
                    ? date('Y-m-d', strtotime('+1 year'))
                    : date('Y-m-d', strtotime('+30 days'));
                
                // Update user subscription
                $stmt = $pdo->prepare('UPDATE platform_users SET subscription_status = "active", subscription_expires = ? WHERE id = ?');
                $stmt->execute([$endDate, $userId]);
                
                // Record revenue
                $stmt = $pdo->prepare('INSERT INTO platform_revenue (user_id, amount, type, payment_method, mpesa_receipt, description, recorded_by) VALUES (?, ?, "subscription", "paystack", ?, ?, 1)');
                $stmt->execute([$userId, $data['amount'] / 100, $reference, "Subscription: $plan ($billing)"]);
                
                // Update pending subscription
                $stmt = $pdo->prepare('UPDATE pending_subscriptions SET status = "completed", completed_at = NOW() WHERE reference = ?');
                $stmt->execute([$reference]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'plan' => $plan,
                    'expires' => $endDate,
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment verification failed',
                ]);
            }
            break;
        
        // ═══ GET USER SUBSCRIPTION ═══
        case 'subscription':
            $userId = (int)($_SESSION['user_id'] ?? 0);
            
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                exit;
            }
            
            $stmt = $pdo->prepare('SELECT subscription_status, subscription_expires, max_animals, max_fields, max_users FROM platform_users WHERE id = ?');
            $stmt->execute([$userId]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sub) {
                $stmt = $pdo->prepare('SELECT "trial" as subscription_status, DATE_ADD(created_at, INTERVAL 30 DAY) as subscription_expires, 5 as max_animals, 5 as max_fields, 5 as max_users FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            echo json_encode($sub ?: ['subscription_status' => 'trial', 'max_animals' => 5, 'max_fields' => 5, 'max_users' => 5]);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
