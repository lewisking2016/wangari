<?php
/**
 * Google OAuth 2.0 Configuration
 * Integrates Google Login into Wangari Farm OS.
 */
declare(strict_types=1);

// Load local database config/override if exists (to resolve private credentials)
$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    @include $localConfig;
}

// Env helper
$oauthEnv = function (string $key, string $default = ''): string {
    $val = $_ENV[$key] ?? getenv($key);
    return ($val === false || $val === '') ? $default : (string)$val;
};

// OAuth credentials
define('GOOGLE_CLIENT_ID', $oauthEnv('GOOGLE_CLIENT_ID', '130893240175-4uj4716lrfgl3idul3aadu025l8oin46.apps.googleusercontent.com'));
define('GOOGLE_CLIENT_SECRET', $oauthEnv('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE'));

// Redirect URI resolver
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) === 443;
$httpProtocol = $isHttps ? 'https' : 'http';
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
define('GOOGLE_REDIRECT_URI', $oauthEnv('GOOGLE_REDIRECT_URI', "{$httpProtocol}://{$httpHost}/Frontend/auth/google/callback.php"));

/**
 * Generate Google Authorization URL
 */
function getGoogleAuthUrl(string $state = ''): string
{
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
        'access_type'   => 'online',
        'prompt'        => 'select_account',
        'state'         => $state
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Exchange Authorization Code for Access Token
 */
function getGoogleAccessToken(string $code): ?array
{
    $url = 'https://oauth2.googleapis.com/token';
    $params = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        @error_log("Google Token Exchange Curl Error: " . $err);
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) && isset($data['access_token']) ? $data : null;
}

/**
 * Fetch Google User Profile details using Access Token
 */
function getGoogleUserProfile(string $accessToken): ?array
{
    $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        @error_log("Google Profile Info Curl Error: " . $err);
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) && isset($data['email']) ? $data : null;
}
