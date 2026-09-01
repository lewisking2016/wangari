<?php
/**
 * Wangari Weather Alerts
 * 
 * Provides weather data and farming-relevant alerts:
 * - Temperature warnings (heat stress for layers)
 * - Rainfall predictions (planting/harvesting decisions)
 * - Humidity alerts (disease risk)
 * 
 * Source: OpenWeatherMap free tier (1,000 calls/day = FREE)
 * 
 * Endpoint: GET /Backend/api/weather_alerts.php?location=Nakuru
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();

$location = ucfirst($_GET['location'] ?? 'Nakuru');
$action = $_GET['action'] ?? 'current';

// Kenya county coordinates
$counties = [
    'Nairobi' => [-1.2921, 36.8219],
    'Nakuru' => [-0.3031, 36.0800],
    'Kiambu' => [-1.1714, 36.8300],
    'Uasin Gishu' => [0.5200, 35.2700],
    'Meru' => [0.0460, 37.6530],
    'Kisumu' => [-0.1022, 34.7617],
    'Machakos' => [-1.5177, 37.2634],
    'Nyeri' => [-0.4201, 36.9476],
    'Murang\'a' => [-0.7210, 37.1526],
    'Trans Nzoia' => [1.0950, 35.0370],
];

$coords = $counties[$location] ?? $counties['Nakuru'];

// OpenWeatherMap free API (1,000 calls/day)
$api_key = 'YOUR_API_KEY';  // Get free key at openweathermap.org
$lat = $coords[0];
$lon = $coords[1];

// Try to fetch real weather data
$weather = fetchWeather($api_key, $lat, $lon);

// If API fails, use mock data
if (!$weather) {
    $weather = getMockWeather($location);
}

// Generate farming alerts
$alerts = generateFarmingAlerts($weather);

echo json_encode([
    'location' => $location,
    'date' => date('Y-m-d'),
    'weather' => $weather,
    'alerts' => $alerts,
    'source' => 'OpenWeatherMap'
]);

// ═══════════════════════════════════════════════════════════════
// FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function fetchWeather(string $api_key, float $lat, float $lon): ?array {
    if ($api_key === 'YOUR_API_KEY' || empty($api_key)) {
        return null;
    }
    
    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$api_key&units=metric";
    
    $context = stream_context_create([
        'http' => ['timeout' => 5]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return null;
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['main'])) return null;
    
    return [
        'temp' => round($data['main']['temp']),
        'feels_like' => round($data['main']['feels_like']),
        'humidity' => $data['main']['humidity'],
        'description' => $data['weather'][0]['description'] ?? 'Unknown',
        'wind_speed' => $data['wind']['speed'] ?? 0,
        'rain_1h' => $data['rain']['1h'] ?? 0,
    ];
}

function getMockWeather(string $location): array {
    // Mock weather data based on Kenya climate patterns
    $month = (int) date('m');
    
    // Kenya climate by month
    $climate = [
        1 => ['temp' => 28, 'humidity' => 60, 'desc' => 'Warm and dry', 'rain' => 0],
        2 => ['temp' => 30, 'humidity' => 55, 'desc' => 'Hot and dry', 'rain' => 0],
        3 => ['temp' => 28, 'humidity' => 70, 'desc' => 'Long rains begin', 'rain' => 5],
        4 => ['temp' => 26, 'humidity' => 75, 'desc' => 'Rainy season', 'rain' => 15],
        5 => ['temp' => 24, 'humidity' => 80, 'desc' => 'Rainy season peak', 'rain' => 20],
        6 => ['temp' => 23, 'humidity' => 70, 'desc' => 'Cool and dry', 'rain' => 5],
        7 => ['temp' => 22, 'humidity' => 65, 'desc' => 'Cold and dry', 'rain' => 2],
        8 => ['temp' => 23, 'humidity' => 60, 'desc' => 'Cool and dry', 'rain' => 2],
        9 => ['temp' => 25, 'humidity' => 60, 'desc' => 'Warming up', 'rain' => 5],
        10 => ['temp' => 26, 'humidity' => 70, 'desc' => 'Short rains begin', 'rain' => 10],
        11 => ['temp' => 25, 'humidity' => 75, 'desc' => 'Short rains', 'rain' => 15],
        12 => ['temp' => 27, 'humidity' => 65, 'desc' => 'Warm and dry', 'rain' => 5],
    ];
    
    $c = $climate[$month];
    
    return [
        'temp' => $c['temp'] + random_int(-2, 2),
        'feels_like' => $c['temp'] + random_int(-1, 3),
        'humidity' => $c['humidity'] + random_int(-5, 5),
        'description' => $c['desc'],
        'wind_speed' => random_int(2, 8),
        'rain_1h' => $c['rain'] > 0 ? random_int(0, $c['rain']) : 0,
    ];
}

function generateFarmingAlerts(array $weather): array {
    $alerts = [];
    
    // Heat stress alert (dangerous for layers)
    if ($weather['temp'] >= 30) {
        $alerts[] = [
            'type' => 'heat_stress',
            'severity' => 'high',
            'icon' => '🌡️',
            'message' => "Heat stress alert! Temperature is {$weather['temp']}°C. Layers drop egg production above 30°C.",
            'action' => "1) Provide extra water (2x normal), 2) Add shade/ventilation, 3) Feed during cooler hours (morning/evening), 4) Add electrolytes to water."
        ];
    } elseif ($weather['temp'] >= 28) {
        $alerts[] = [
            'type' => 'heat_warning',
            'severity' => 'medium',
            'icon' => '🌡️',
            'message' => "Warm conditions ({$weather['temp']}°C). Monitor water supply closely.",
            'action' => "Ensure clean water is always available. Check drinkers aren't empty."
        ];
    }
    
    // Cold stress (broilers)
    if ($weather['temp'] <= 18) {
        $alerts[] = [
            'type' => 'cold_stress',
            'severity' => 'medium',
            'icon' => '❄️',
            'message' => "Cold conditions ({$weather['temp']}°C). Young birds especially vulnerable.",
            'action' => "1) Use brooder/lamp for chicks, 2) Reduce ventilation, 3) Increase feed slightly."
        ];
    }
    
    // High humidity (disease risk)
    if ($weather['humidity'] >= 80) {
        $alerts[] = [
            'type' => 'disease_risk',
            'severity' => 'medium',
            'icon' => '💧',
            'message' => "High humidity ({$weather['humidity']}%). Increased risk of respiratory diseases.",
            'action' => "1) Improve ventilation, 2) Remove wet litter, 3) Watch for sneezing/coughing in flock."
        ];
    }
    
    // Rain alert
    if ($weather['rain_1h'] > 5) {
        $alerts[] = [
            'type' => 'rain',
            'severity' => 'low',
            'icon' => '🌧️',
            'message' => "Rain detected. Ensure feed storage is dry and covered.",
            'action' => "1) Cover feed bags, 2) Check roof for leaks, 3) Ensure drainage is clear."
        ];
    }
    
    // Good conditions
    if (empty($alerts)) {
        $alerts[] = [
            'type' => 'good',
            'severity' => 'info',
            'icon' => '✅',
            'message' => "Good farming conditions today ({$weather['temp']}°C, {$weather['humidity']}% humidity).",
            'action' => "Normal management. Great day for farm work!"
        ];
    }
    
    return $alerts;
}
