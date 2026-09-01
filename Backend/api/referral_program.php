<?php
/**
 * Wangari Referral Program
 * 
 * "Bring 5 Farmers, Get 1 Month Free"
 * 
 * Features:
 * - Generate unique referral codes per user
 * - Track who invited whom
 * - Auto-apply credits when referral is confirmed
 * - Leaderboard for top referrers
 * 
 * Endpoint: 
 *   GET /Backend/api/referral_program.php?action=code&user_id=123
 *   GET /Backend/api/referral_program.php?action=status&user_id=123
 *   GET /Backend/api/referral_program.php?action=leaderboard
 *   POST /Backend/api/referral_program.php (apply referral code)
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'status');
$user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

switch ($action) {
    case 'code':
        // Get or generate referral code
        if ($user_id <= 0) {
            echo json_encode(['error' => 'user_id required']);
            exit;
        }
        echo json_encode(getReferralCode($pdo, $user_id));
        break;
    
    case 'status':
        // Get referral status for a user
        if ($user_id <= 0) {
            echo json_encode(['error' => 'user_id required']);
            exit;
        }
        echo json_encode(getReferralStatus($pdo, $user_id));
        break;
    
    case 'apply':
        // Apply a referral code (when new user signs up)
        $code = trim($_POST['code'] ?? '');
        $new_user_id = (int)($_POST['new_user_id'] ?? 0);
        
        if (empty($code) || $new_user_id <= 0) {
            echo json_encode(['error' => 'code and new_user_id required']);
            exit;
        }
        echo json_encode(applyReferral($pdo, $code, $new_user_id));
        break;
    
    case 'leaderboard':
        // Top referrers
        echo json_encode(getLeaderboard($pdo));
        break;
    
    default:
        echo json_encode(['error' => 'Unknown action']);
}

// ═══════════════════════════════════════════════════════════════
// FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getReferralCode(PDO $pdo, int $user_id): array {
    // Check if code already exists
    $stmt = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing && !empty($existing['referral_code'])) {
        return [
            'code' => $existing['referral_code'],
            'link' => "https://wangari.imeantech.com/Frontend/pages/register.php?ref=" . $existing['referral_code'],
            'message' => "Share this code with farmer friends!"
        ];
    }
    
    // Generate new code
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $base = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $user['username'] ?? 'FARMER'), 0, 5));
    $code = $base . random_int(1000, 9999);
    
    // Ensure uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
    $stmt->execute([$code]);
    while ($stmt->fetch()) {
        $code = $base . random_int(1000, 9999);
        $stmt->execute([$code]);
    }
    
    // Save code
    $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$code, $user_id]);
    
    return [
        'code' => $code,
        'link' => "https://wangari.imeantech.com/Frontend/pages/register.php?ref=" . $code,
        'message' => "Your referral code is ready! Share it with 5 farming friends and get 1 month free."
    ];
}

function getReferralStatus(PDO $pdo, int $user_id): array {
    // Get total referrals
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
    $stmt->execute([$user_id]);
    $total_referrals = (int) $stmt->fetchColumn();
    
    // Get active referrals (7+ day streak)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_referrals = (int) $stmt->fetchColumn();
    
    // Get free months earned
    $free_months = intdiv($active_referrals, 5);
    
    // Get referrals still needed
    $needed_for_next = 5 - ($active_referrals % 5);
    
    // Get recent referrals
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name, u.created_at 
        FROM referrals r 
        JOIN users u ON r.referred_id = u.id 
        WHERE r.referrer_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_referrals' => $total_referrals,
        'active_referrals' => $active_referrals,
        'free_months_earned' => $free_months,
        'needed_for_next' => $needed_for_next,
        'progress' => ($active_referrals % 5) . "/5",
        'recent' => $recent,
        'message' => $free_months > 0
            ? "🎉 You've earned {$free_months} free month(s)! $active_referrals farmers signed up through your code."
            : ($total_referrals > 0
                ? "You have $total_referrals referrals ($active_referrals active). Need " . ($needed_for_next) . " more for 1 free month!"
                : "Share your code with 5 farming friends to get 1 month free!")
    ];
}

function applyReferral(PDO $pdo, string $code, int $new_user_id): array {
    // Find the referrer
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE referral_code = ?");
    $stmt->execute([$code]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$referrer) {
        return ['error' => 'Invalid referral code'];
    }
    
    if ($referrer['id'] == $new_user_id) {
        return ['error' => 'You cannot refer yourself'];
    }
    
    // Check if already referred
    $stmt = $pdo->prepare("SELECT id FROM referrals WHERE referred_id = ?");
    $stmt->execute([$new_user_id]);
    if ($stmt->fetch()) {
        return ['error' => 'This user was already referred'];
    }
    
    // Record the referral
    $pdo->prepare("INSERT INTO referrals (referrer_id, referred_id, code_used, status, created_at) VALUES (?, ?, ?, 'pending', NOW())")
        ->execute([$referrer['id'], $new_user_id, $code]);
    
    // Update referred_by on user
    $pdo->prepare("UPDATE users SET referred_by = ? WHERE id = ?")->execute([$referrer['id'], $new_user_id]);
    
    return [
        'success' => true,
        'referrer_name' => $referrer['full_name'],
        'message' => "Welcome! You were referred by {$referrer['full_name']}. They'll get a free month when you stay active for 7 days!"
    ];
}

function getLeaderboard(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            COALESCE(u.full_name, u.username) as name,
            COUNT(r.id) as total_referrals,
            SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) as active_referrals,
            FLOOR(SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) / 5) as free_months
        FROM users u
        LEFT JOIN referrals r ON u.id = r.referrer_id
        GROUP BY u.id
        HAVING total_referrals > 0
        ORDER BY active_referrals DESC
        LIMIT 20
    ");
    $stmt->execute();
    $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'leaders' => $leaders,
        'total_referrals_all' => array_sum(array_column($leaders, 'total_referrals')),
        'message' => "Top referrers on Wangari!"
    ];
}
