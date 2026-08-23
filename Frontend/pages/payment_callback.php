<?php
/**
 * Payment Callback Page
 * Handles redirect after Paystack payment
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
require_once dirname(__DIR__, 2) . '/Backend/config/database.php';

wangariStartSession();

$reference = $_GET['reference'] ?? '';
$trxref = $_GET['trxref'] ?? '';

// Use reference or trxref
$paymentRef = $reference ?: $trxref;

if (!$paymentRef) {
    header('Location: /Frontend/pages/pricing.php?error=no_reference');
    exit;
}

// Verify payment via API
require_once dirname(__DIR__, 2) . '/Backend/config/paystack.php';

$result = paystackVerifyTransaction($paymentRef);

if ($result['status'] && $result['data']['status'] === 'success') {
    // Payment successful
    $data = $result['data'];
    $metadata = $data['metadata'] ?? [];
    $plan = strtoupper($metadata['plan'] ?? '');
    $billing = $metadata['billing'] ?? 'monthly';
    
    // Redirect to success page
    header('Location: /Frontend/pages/payment_success.php?plan=' . urlencode($plan) . '&billing=' . urlencode($billing));
    exit;
} else {
    // Payment failed or pending
    header('Location: /Frontend/pages/pricing.php?status=failed&reference=' . urlencode($paymentRef));
    exit;
}
