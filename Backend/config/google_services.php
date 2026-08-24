<?php
/**
 * Google Services Integration for Wangari
 * 
 * Handles Gmail notifications and Google Calendar sync.
 * Extends the existing Google OAuth to include Gmail and Calendar scopes.
 */

require_once __DIR__ . '/google_oauth.php';

// ═══════════════════════════════════════════════════════════════
// Extended Scopes for Gmail + Calendar
// ═══════════════════════════════════════════════════════════════

define('GOOGLE_SCOPES_BASIC', [
    'https://www.googleapis.com/auth/userinfo.profile',
    'https://www.googleapis.com/auth/userinfo.email',
]);

define('GOOGLE_SCOPES_GMAIL', [
    'https://www.googleapis.com/auth/gmail.send',           // Send emails
    'https://www.googleapis.com/auth/gmail.readonly',       // Read emails (optional)
]);

define('GOOGLE_SCOPES_CALENDAR', [
    'https://www.googleapis.com/auth/calendar',             // Full calendar access
    'https://www.googleapis.com/auth/calendar.events',      // Manage events
]);

// All scopes combined
define('GOOGLE_SCOPES_ALL', array_unique(array_merge(
    GOOGLE_SCOPES_BASIC,
    GOOGLE_SCOPES_GMAIL,
    GOOGLE_SCOPES_CALENDAR
)));

// ═══════════════════════════════════════════════════════════════
// Authorization URL with extended scopes
// ═══════════════════════════════════════════════════════════════

/**
 * Generate Google Authorization URL for Gmail + Calendar
 */
function getGoogleServicesAuthUrl(string $state = 'services'): string
{
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => getServicesRedirectUri(),
        'response_type' => 'code',
        'scope'         => implode(' ', GOOGLE_SCOPES_ALL),
        'access_type'   => 'offline',           // Get refresh token
        'prompt'        => 'consent',            // Force consent to get refresh token
        'state'         => $state
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Get redirect URI for services OAuth
 */
function getServicesRedirectUri(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    $httpProtocol = $isHttps ? 'https' : 'http';
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    
    return "{$httpProtocol}://{$httpHost}/Frontend/auth/google/services_callback.php";
}

/**
 * Exchange authorization code for tokens (with refresh token)
 */
function getGoogleServicesTokens(string $code): ?array
{
    $url = 'https://oauth2.googleapis.com/token';
    $params = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => getServicesRedirectUri(),
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return is_array($data) && isset($data['access_token']) ? $data : null;
}

/**
 * Refresh an expired access token
 */
function refreshGoogleToken(int $userId): ?string
{
    require_once __DIR__ . '/database.php';
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("SELECT refresh_token FROM user_google_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row || empty($row['refresh_token'])) {
        return null;
    }
    
    $url = 'https://oauth2.googleapis.com/token';
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'refresh_token' => $row['refresh_token'],
        'grant_type'    => 'refresh_token'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    
    if (isset($data['access_token'])) {
        // Update stored access token
        $expiresAt = date('Y-m-d H:i:s', time() + ($data['expires_in'] ?? 3600));
        $stmt = $pdo->prepare("UPDATE user_google_tokens SET access_token = ?, expires_at = ? WHERE user_id = ?");
        $stmt->execute([$data['access_token'], $expiresAt, $userId]);
        
        return $data['access_token'];
    }
    
    return null;
}

/**
 * Get valid access token for user (refresh if needed)
 */
function getValidGoogleToken(int $userId): ?string
{
    require_once __DIR__ . '/database.php';
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("SELECT access_token, expires_at FROM user_google_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) return null;
    
    // Check if token is expired (with 5 min buffer)
    if (strtotime($row['expires_at']) > time() + 300) {
        return $row['access_token'];
    }
    
    // Token expired, refresh it
    return refreshGoogleToken($userId);
}

// ═══════════════════════════════════════════════════════════════
// Gmail Functions
// ═══════════════════════════════════════════════════════════════

/**
 * Send email via Gmail API
 */
function sendGmailMessage(int $userId, string $to, string $subject, string $body, bool $isHtml = true): bool
{
    $accessToken = getValidGoogleToken($userId);
    if (!$accessToken) return false;
    
    // Build the email message
    $email = "To: {$to}\r\n";
    $email .= "From: me\r\n";
    $email .= "Subject: {$subject}\r\n";
    $email .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
    $email .= "\r\n";
    $email .= $body;
    
    // Base64url encode
    $raw = rtrim(strtr(base64_encode($email), '+/', '-_'), '=');
    
    $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';
    $payload = json_encode(['raw' => $raw]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json",
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Send farm notification email
 */
function sendFarmNotification(int $userId, string $type, string $title, string $message): bool
{
    require_once __DIR__ . '/database.php';
    $pdo = getDatabaseConnection();
    
    // Get user email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userEmail = $stmt->fetchColumn();
    
    if (!$userEmail) return false;
    
    // Build HTML email
    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 24px;'>🌾 Wangari Farm Alert</h1>
        </div>
        <div style='background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0;'>
            <div style='background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #22C55E;'>
                <h2 style='margin: 0 0 10px 0; color: #1e293b;'>{$title}</h2>
                <p style='margin: 0; color: #64748b;'>{$message}</p>
            </div>
            <p style='color: #94a3b8; font-size: 12px; text-align: center;'>
                This is an automated notification from Wangari Farm OS.<br>
                <a href='https://wangari.imeantech.com/Frontend/admin/dashboard.php' style='color: #22C55E;'>Open Dashboard</a>
            </p>
        </div>
    </div>";
    
    return sendGmailMessage($userId, $userEmail, "[Wangari] {$title}", $html, true);
}

// ═══════════════════════════════════════════════════════════════
// Calendar Functions
// ═══════════════════════════════════════════════════════════════

/**
 * Create Google Calendar event
 */
function createCalendarEvent(int $userId, string $summary, string $description, string $startDateTime, string $endDateTime, array $reminders = []): ?string
{
    $accessToken = getValidGoogleToken($userId);
    if (!$accessToken) return null;
    
    if (empty($reminders)) {
        $reminders = [
            ['method' => 'email', 'minutes' => 24 * 60],   // 1 day before
            ['method' => 'popup', 'minutes' => 60],         // 1 hour before
        ];
    }
    
    $event = [
        'summary' => $summary,
        'description' => $description,
        'start' => [
            'dateTime' => $startDateTime,
            'timeZone' => 'Africa/Nairobi',
        ],
        'end' => [
            'dateTime' => $endDateTime,
            'timeZone' => 'Africa/Nairobi',
        ],
        'reminders' => [
            'useDefault' => false,
            'overrides' => $reminders,
        ],
    ];
    
    $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json",
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        return $data['id'] ?? null;
    }
    
    return null;
}

/**
 * Sync vaccination schedule to Google Calendar
 */
function syncVaccinationToCalendar(int $userId, string $flockName, string $vaccineName, string $dueDate): ?string
{
    $start = $dueDate . 'T09:00:00';
    $end = $dueDate . 'T10:00:00';
    
    $summary = "Vaccination: {$vaccineName} - {$flockName}";
    $description = "Vaccination reminder for {$flockName}\nVaccine: {$vaccineName}\nDate: {$dueDate}\n\nPlease ensure all birds are vaccinated on this date.";
    
    return createCalendarEvent($userId, $summary, $description, $start, $end, [
        ['method' => 'email', 'minutes' => 24 * 60],    // 1 day before
        ['method' => 'email', 'minutes' => 3 * 24 * 60], // 3 days before
        ['method' => 'popup', 'minutes' => 60],           // 1 hour before
    ]);
}

/**
 * Sync feeding reminder to Google Calendar
 */
function syncFeedingReminderToCalendar(int $userId, string $flockName, string $feedType, string $time, string $recurring = 'DAILY'): ?string
{
    $today = date('Y-m-d');
    $start = $today . 'T' . $time . ':00';
    $end = $today . 'T' . date('H:i:s', strtotime($time . ' + 30 minutes'));
    
    $summary = "Feed {$flockName} - {$feedType}";
    $description = "Feeding time for {$flockName}\nFeed type: {$feedType}\nTime: {$time}";
    
    return createCalendarEvent($userId, $summary, $description, $start, $end);
}

// ═══════════════════════════════════════════════════════════════
// Notification Scheduler
// ═══════════════════════════════════════════════════════════════

/**
 * Check and send upcoming vaccination reminders
 */
function checkVaccinationReminders(): void
{
    require_once __DIR__ . '/database.php';
    $pdo = getDatabaseConnection();
    
    // Get vaccinations due in next 3 days
    $stmt = $pdo->prepare("
        SELECT v.*, f.flock_name, f.user_id
        FROM vaccinations v
        JOIN flocks f ON v.flock_id = f.id
        WHERE v.next_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        AND v.status = 'pending'
    ");
    $stmt->execute();
    $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($vaccinations as $vax) {
        $daysUntil = (strtotime($vax['next_date']) - time()) / 86400;
        
        $title = "Vaccination Due: {$vax['vaccine_name']}";
        $message = "Your {$vax['flock_name']} flock needs {$vax['vaccine_name']} vaccination on {$vax['next_date']}.";
        
        if ($daysUntil <= 1) {
            $message = "URGENT: " . $message;
        }
        
        sendFarmNotification($vax['user_id'], 'vaccination', $title, $message);
    }
}

/**
 * Check and send low stock alerts
 */
function checkLowStockAlerts(): void
{
    require_once __DIR__ . '/database.php';
    $pdo = getDatabaseConnection();
    
    // Check feed stock
    $stmt = $pdo->query("
        SELECT s.*, u.id as user_id
        FROM store_products s
        JOIN users u ON s.farm_id = u.farm_id
        WHERE s.quantity <= s.reorder_level
        AND s.status = 'active'
    ");
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lowStock as $item) {
        $title = "Low Stock Alert: {$item['name']}";
        $message = "{$item['name']} is running low. Current stock: {$item['quantity']} {$item['unit']}. Please reorder.";
        
        sendFarmNotification($item['user_id'], 'stock', $title, $message);
    }
}

/**
 * Send daily farm summary
 */
function sendDailyFarmSummary(int $userId): void
{
    require_once __DIR__ . '/database.php';
    $pdo = getDatabaseConnection();
    
    // Get farm stats
    $flockCount = $pdo->query("SELECT COUNT(*) FROM flocks WHERE status='active'")->fetchColumn();
    $animalCount = $pdo->query("SELECT COUNT(*) FROM animals WHERE status='active'")->fetchColumn();
    $eggCount = $pdo->query("SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE recorded_date=CURDATE()")->fetchColumn();
    $milkLiters = $pdo->query("SELECT COALESCE(SUM(morning_liters+evening_liters),0) FROM milking_records WHERE recorded_date=CURDATE()")->fetchColumn();
    
    $title = "Daily Farm Summary - " . date('F j, Y');
    $message = "Today's Summary:\n";
    $message .= "- Active Flocks: {$flockCount}\n";
    $message .= "- Active Animals: {$animalCount}\n";
    $message .= "- Eggs Collected: {$eggCount}\n";
    $message .= "- Milk Produced: {$milkLiters} liters\n";
    
    sendFarmNotification($userId, 'summary', $title, $message);
}
