<?php
/**
 * Weather Integration for Wangari Farm
 * 
 * Features:
 * - Real-time weather data
 * - 7-day forecast
 * - Weather alerts for farming
 * - Seasonal farming advice
 * - Integration with AI assistant
 */

class WeatherService {
    private $apiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5';
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: getenv('OPENWEATHER_API_KEY');
    }
    
    /**
     * Get current weather by city
     */
    public function getCurrentWeather($city = 'Nairobi') {
        if (!$this->apiKey) {
            return $this->getDefaultWeather();
        }
        
        $url = "{$this->baseUrl}/weather?q={$city}&appid={$this->apiKey}&units=metric";
        $response = $this->makeRequest($url);
        
        if ($response) {
            return [
                'temperature' => $response['main']['temp'],
                'feels_like' => $response['main']['feels_like'],
                'humidity' => $response['main']['humidity'],
                'description' => $response['weather'][0]['description'],
                'icon' => $response['weather'][0]['icon'],
                'wind_speed' => $response['wind']['speed'],
                'city' => $response['name'],
                'country' => $response['sys']['country'],
                'sunrise' => date('H:i', $response['sys']['sunrise']),
                'sunset' => date('H:i', $response['sys']['sunset'])
            ];
        }
        
        return $this->getDefaultWeather();
    }
    
    /**
     * Get 5-day forecast
     */
    public function getForecast($city = 'Nairobi') {
        if (!$this->apiKey) {
            return $this->getDefaultForecast();
        }
        
        $url = "{$this->baseUrl}/forecast?q={$city}&appid={$this->apiKey}&units=metric";
        $response = $this->makeRequest($url);
        
        if ($response) {
            $forecast = [];
            $seen_dates = [];
            
            foreach ($response['list'] as $item) {
                $date = date('Y-m-d', $item['dt']);
                $hour = date('H', $item['dt']);
                
                // Only take one forecast per day (noon)
                if (!in_array($date, $seen_dates) && $hour >= 11 && $hour <= 14) {
                    $seen_dates[] = $date;
                    $forecast[] = [
                        'date' => $date,
                        'day' => date('l', strtotime($date)),
                        'temp_min' => $item['main']['temp_min'],
                        'temp_max' => $item['main']['temp_max'],
                        'description' => $item['weather'][0]['description'],
                        'icon' => $item['weather'][0]['icon'],
                        'humidity' => $item['main']['humidity'],
                        'rain_chance' => isset($item['pop']) ? round($item['pop'] * 100) : 0
                    ];
                }
                
                if (count($forecast) >= 5) break;
            }
            
            return $forecast;
        }
        
        return $this->getDefaultForecast();
    }
    
    /**
     * Get farming advice based on weather
     */
    public function getFarmingAdvice($weather) {
        $advice = [];
        $temp = $weather['temperature'];
        $humidity = $weather['humidity'];
        $description = strtolower($weather['description']);
        
        // Temperature advice
        if ($temp > 30) {
            $advice[] = [
                'type' => 'warning',
                'icon' => '🌡️',
                'message' => 'High temperature! Ensure animals have shade and plenty of water.'
            ];
        } elseif ($temp < 15) {
            $advice[] = [
                'type' => 'warning',
                'icon' => '❄️',
                'message' => 'Cold weather. Keep young animals warm and increase feed.'
            ];
        }
        
        // Rain advice
        if (strpos($description, 'rain') !== false) {
            $advice[] = [
                'type' => 'info',
                'icon' => '🌧️',
                'message' => 'Rain expected. Ensure good drainage and keep feed dry.'
            ];
        }
        
        // Humidity advice
        if ($humidity > 80) {
            $advice[] = [
                'type' => 'warning',
                'icon' => '💧',
                'message' => 'High humidity. Improve ventilation to prevent disease.'
            ];
        } elseif ($humidity < 30) {
            $advice[] = [
                'type' => 'info',
                'icon' => '☀️',
                'message' => 'Dry conditions. Ensure adequate water for all animals.'
            ];
        }
        
        // Wind advice
        if (isset($weather['wind_speed']) && $weather['wind_speed'] > 10) {
            $advice[] = [
                'type' => 'warning',
                'icon' => '💨',
                'message' => 'Strong winds. Secure loose structures and provide windbreaks.'
            ];
        }
        
        return $advice;
    }
    
    /**
     * Get weather alerts for farming
     */
    public function getWeatherAlerts($forecast) {
        $alerts = [];
        
        foreach ($forecast as $day) {
            // Heavy rain alert
            if ($day['rain_chance'] > 70) {
                $alerts[] = [
                    'date' => $day['day'],
                    'type' => 'rain',
                    'severity' => 'high',
                    'message' => "Heavy rain expected on {$day['day']}. Prepare drainage and protect feed."
                ];
            }
            
            // Extreme temperature
            if ($day['temp_max'] > 35) {
                $alerts[] = [
                    'date' => $day['day'],
                    'type' => 'heat',
                    'severity' => 'high',
                    'message' => "Very hot on {$day['day']} ({$day['temp_max']}°C). Provide extra shade and water."
                ];
            }
            
            if ($day['temp_min'] < 12) {
                $alerts[] = [
                    'date' => $day['day'],
                    'type' => 'cold',
                    'severity' => 'medium',
                    'message' => "Cold night on {$day['day']} ({$day['temp_min']}°C). Keep animals warm."
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get seasonal farming calendar
     */
    public function getSeasonalCalendar($month = null) {
        $month = $month ?: date('n');
        
        $calendar = [
            1 => [ // January
                'season' => 'Dry Season',
                'activities' => [
                    '🐔 Start new chicken batches',
                    '🌾 Plant early season crops',
                    '💧 conserve water',
                    '🐄 Provide extra water for livestock'
                ]
            ],
            2 => [ // February
                'season' => 'Dry Season',
                'activities' => [
                    '🐔 Continue broiler production',
                    '🌾 Prepare for long rains',
                    '🔧 Repair farm structures',
                    '💉 Vaccinate animals'
                ]
            ],
            3 => [ // March
                'season' => 'Long Rains',
                'activities' => [
                    '🌾 Plant maize, beans, vegetables',
                    '🐔 Watch for coccidiosis (wet conditions)',
                    '💧 Ensure drainage is clear',
                    '🌱 Transplant seedlings'
                ]
            ],
            4 => [ // April
                'season' => 'Long Rains',
                'activities' => [
                    '🌾 Weeding and fertilizer application',
                    '🐔 Monitor for diseases',
                    '🐄 Provide shelter from rain',
                    '🌿 Plant Napier grass'
                ]
            ],
            5 => [ // May
                'season' => 'Long Rains',
                'activities' => [
                    '🌾 Top-dress crops with CAN',
                    '🐔 Continue disease prevention',
                    '🐄 Harvest hay for dry season',
                    '💧 Harvest rainwater'
                ]
            ],
            6 => [ // June
                'season' => 'End of Rains',
                'activities' => [
                    '🌾 Harvest early crops',
                    '🐔 Sell mature broilers',
                    '🐄 Prepare for dry season',
                    '储存 Store feed properly'
                ]
            ],
            7 => [ // July
                'season' => 'Dry Season',
                'activities' => [
                    '🌾 Harvest maize',
                    '🐔 Reduce chicken stock if feed scarce',
                    '🐄 Buy hay for dry season',
                    '🔧 Maintenance work'
                ]
            ],
            8 => [ // August
                'season' => 'Dry Season',
                'activities' => [
                    '🌾 Thresh and store grain',
                    '🐔 Start new layer batches',
                    '🐄 Supplement with dairy meal',
                    '💉 Deworm all animals'
                ]
            ],
            9 => [ // September
                'season' => 'Short Rains',
                'activities' => [
                    '🌾 Plant short season crops',
                    '🐔 Watch for disease outbreaks',
                    '💧 Prepare drainage',
                    '🌱 Plant vegetables'
                ]
            ],
            10 => [ // October
                'season' => 'Short Rains',
                'activities' => [
                    '🌾 Weeding and pest control',
                    '🐔 Vaccinate new birds',
                    '🐄 Provide shelter',
                    '🌿 Harvest Napier grass'
                ]
            ],
            11 => [ // November
                'season' => 'Short Rains',
                'activities' => [
                    '🌾 Harvest beans, vegetables',
                    '🐔 Sell mature birds',
                    '🐄 Prepare for dry season',
                    '储存 Store harvest'
                ]
            ],
            12 => [ // December
                'season' => 'Dry Season',
                'activities' => [
                    '🎄 Holiday period - maintain routines',
                    '🐔 Sell for Christmas demand',
                    '🌾 Plan for next year',
                    '📊 Review farm performance'
                ]
            ]
        ];
        
        return $calendar[$month] ?? $calendar[1];
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    private function getDefaultWeather() {
        return [
            'temperature' => 24,
            'feels_like' => 25,
            'humidity' => 65,
            'description' => 'partly cloudy',
            'icon' => '02d',
            'wind_speed' => 5,
            'city' => 'Nairobi',
            'country' => 'KE',
            'sunrise' => '06:30',
            'sunset' => '18:45'
        ];
    }
    
    private function getDefaultForecast() {
        return [
            [
                'date' => date('Y-m-d'),
                'day' => date('l'),
                'temp_min' => 18,
                'temp_max' => 26,
                'description' => 'partly cloudy',
                'icon' => '02d',
                'humidity' => 65,
                'rain_chance' => 20
            ]
        ];
    }
}

// API endpoint handler
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'current';
    $city = $_GET['city'] ?? 'Nairobi';
    
    $weather = new WeatherService();
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'current':
            echo json_encode($weather->getCurrentWeather($city));
            break;
        case 'forecast':
            echo json_encode($weather->getForecast($city));
            break;
        case 'advice':
            $current = $weather->getCurrentWeather($city);
            echo json_encode($weather->getFarmingAdvice($current));
            break;
        case 'alerts':
            $forecast = $weather->getForecast($city);
            echo json_encode($weather->getWeatherAlerts($forecast));
            break;
        case 'calendar':
            $month = $_GET['month'] ?? date('n');
            echo json_encode($weather->getSeasonalCalendar($month));
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
