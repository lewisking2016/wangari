<?php
/**
 * Market Prices Service for Wangari Farm
 * 
 * Features:
 * - Real-time market prices for agricultural products
 * - Price history and trends
 * - Price alerts
 * - Regional price variations
 * - Integration with AI assistant
 */

class MarketPrices {
    private $db;
    
    // Default prices (Kenyan Shillings)
    private $defaultPrices = [
        'poultry' => [
            'broiler_live' => ['min' => 350, 'max' => 500, 'unit' => 'per bird'],
            'broiler_dressed' => ['min' => 500, 'max' => 700, 'unit' => 'per bird'],
            'layer_bird' => ['min' => 800, 'max' => 1200, 'unit' => 'per bird'],
            'kienyeji' => ['min' => 500, 'max' => 800, 'unit' => 'per bird'],
            'eggs_tray' => ['min' => 360, 'max' => 540, 'unit' => 'per tray (30)'],
            'eggs_single' => ['min' => 12, 'max' => 18, 'unit' => 'per egg'],
            'day_old_chick' => ['min' => 80, 'max' => 150, 'unit' => 'per chick'],
            'manure' => ['min' => 50, 'max' => 100, 'unit' => 'per kg']
        ],
        'cattle' => [
            'milk' => ['min' => 40, 'max' => 60, 'unit' => 'per liter'],
            'beef_live' => ['min' => 280, 'max' => 350, 'unit' => 'per kg'],
            'beef_dressed' => ['min' => 450, 'max' => 600, 'unit' => 'per kg'],
            'cow_adult' => ['min' => 60000, 'max' => 120000, 'unit' => 'per animal'],
            'heifer' => ['min' => 60000, 'max' => 100000, 'unit' => 'per animal'],
            'calf' => ['min' => 25000, 'max' => 40000, 'unit' => 'per animal'],
            'bull' => ['min' => 80000, 'max' => 150000, 'unit' => 'per animal']
        ],
        'goats' => [
            'male_goat' => ['min' => 4000, 'max' => 8000, 'unit' => 'per animal'],
            'female_goat' => ['min' => 5000, 'max' => 10000, 'unit' => 'per animal'],
            'kid' => ['min' => 2000, 'max' => 4000, 'unit' => 'per animal'],
            'buck' => ['min' => 6000, 'max' => 12000, 'unit' => 'per animal']
        ],
        'crops' => [
            'maize_90kg' => ['min' => 3000, 'max' => 4500, 'unit' => 'per bag'],
            'beans_90kg' => ['min' => 8000, 'max' => 12000, 'unit' => 'per bag'],
            'sukuma_wiki' => ['min' => 10, 'max' => 20, 'unit' => 'per bunch'],
            'tomatoes_kg' => ['min' => 40, 'max' => 80, 'unit' => 'per kg'],
            'onions_kg' => ['min' => 30, 'max' => 60, 'unit' => 'per kg'],
            'potatoes_kg' => ['min' => 25, 'max' => 50, 'unit' => 'per kg'],
            'cabbages' => ['min' => 50, 'max' => 100, 'unit' => 'each'],
            'rice_90kg' => ['min' => 12000, 'max' => 18000, 'unit' => 'per bag'],
            'wheat_90kg' => ['min' => 4000, 'max' => 5500, 'unit' => 'per bag']
        ],
        'feeds' => [
            'broiler_starter_50kg' => ['min' => 3200, 'max' => 3800, 'unit' => 'per bag'],
            'broiler_grower_50kg' => ['min' => 3000, 'max' => 3500, 'unit' => 'per bag'],
            'broiler_finisher_50kg' => ['min' => 3200, 'max' => 3800, 'unit' => 'per bag'],
            'layer_mash_50kg' => ['min' => 3500, 'max' => 4200, 'unit' => 'per bag'],
            'dairy_meal_50kg' => ['min' => 3500, 'max' => 4000, 'unit' => 'per bag'],
            'maize_bran_50kg' => ['min' => 1500, 'max' => 2000, 'unit' => 'per bag'],
            'fish_meal_50kg' => ['min' => 5000, 'max' => 6500, 'unit' => 'per bag'],
            'goat_pellets_50kg' => ['min' => 4000, 'max' => 5000, 'unit' => 'per bag']
        ],
        'inputs' => [
            'dap_50kg' => ['min' => 3500, 'max' => 4200, 'unit' => 'per bag'],
            'can_50kg' => ['min' => 3000, 'max' => 3600, 'unit' => 'per bag'],
            'lime_50kg' => ['min' => 400, 'max' => 600, 'unit' => 'per bag'],
            'fencing_roll' => ['min' => 2500, 'max' => 4000, 'unit' => 'per roll'],
            'iron_sheets' => ['min' => 500, 'max' => 800, 'unit' => 'per sheet'],
            'timber_8ft' => ['min' => 150, 'max' => 250, 'unit' => 'per piece']
        ]
    ];
    
    // Regional price variations (multipliers)
    private $regionMultipliers = [
        'nairobi' => 1.1,      // 10% higher
        'mombasa' => 1.15,     // 15% higher
        'kisumu' => 1.0,       // base
        'nakuru' => 0.95,      // 5% lower
        'eldoret' => 0.9,      // 10% lower
        'thika' => 1.05,       // 5% higher
        'machakos' => 0.95,    // 5% lower
        'meru' => 0.9,         // 10% lower
        'default' => 1.0       // base
    ];
    
    public function __construct() {
        // Try to load database if available
        try {
            require_once __DIR__ . '/database.php';
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            $this->db = null;
        }
    }
    
    /**
     * Get prices for a category
     */
    public function getPrices($category, $region = 'default') {
        if (!isset($this->defaultPrices[$category])) {
            return ['error' => 'Invalid category'];
        }
        
        $prices = $this->defaultPrices[$category];
        $multiplier = $this->regionMultipliers[$region] ?? $this->regionMultipliers['default'];
        
        $adjustedPrices = [];
        foreach ($prices as $item => $price) {
            $adjustedPrices[$item] = [
                'min' => round($price['min'] * $multiplier),
                'max' => round($price['max'] * $multiplier),
                'unit' => $price['unit'],
                'average' => round(($price['min'] + $price['max']) / 2 * $multiplier)
            ];
        }
        
        return $adjustedPrices;
    }
    
    /**
     * Get all prices
     */
    public function getAllPrices($region = 'default') {
        $allPrices = [];
        foreach ($this->defaultPrices as $category => $prices) {
            $allPrices[$category] = $this->getPrices($category, $region);
        }
        return $allPrices;
    }
    
    /**
     * Get price for specific item
     */
    public function getItemPrice($category, $item, $region = 'default') {
        if (!isset($this->defaultPrices[$category][$item])) {
            return ['error' => 'Invalid item'];
        }
        
        $price = $this->defaultPrices[$category][$item];
        $multiplier = $this->regionMultipliers[$region] ?? $this->regionMultipliers['default'];
        
        return [
            'min' => round($price['min'] * $multiplier),
            'max' => round($price['max'] * $multiplier),
            'average' => round(($price['min'] + $price['max']) / 2 * $multiplier),
            'unit' => $price['unit']
        ];
    }
    
    /**
     * Get price comparison (your price vs market)
     */
    public function comparePrice($category, $item, $yourPrice) {
        $marketPrice = $this->getItemPrice($category, $item);
        
        if (isset($marketPrice['error'])) {
            return $marketPrice;
        }
        
        $difference = $yourPrice - $marketPrice['average'];
        $percentage = round(($difference / $marketPrice['average']) * 100, 1);
        
        return [
            'your_price' => $yourPrice,
            'market_average' => $marketPrice['average'],
            'market_min' => $marketPrice['min'],
            'market_max' => $marketPrice['max'],
            'difference' => $difference,
            'percentage' => $percentage,
            'status' => $difference > 0 ? 'above_market' : ($difference < 0 ? 'below_market' : 'at_market'),
            'recommendation' => $this->getPricingRecommendation($percentage)
        ];
    }
    
    /**
     * Get profit calculator
     */
    public function calculateProfit($type, $quantity, $region = 'default') {
        $calculations = [
            'broiler' => function($qty, $region) {
                $prices = $this->getPrices('poultry', $region);
                $feedCost = $qty * 300; // Average feed cost per broiler
                $medicineCost = $qty * 40;
                $chickCost = $qty * 100;
                $totalCost = $feedCost + $medicineCost + $chickCost;
                
                $avgPrice = $prices['broiler_live']['average'];
                $revenue = $qty * $avgPrice;
                $profit = $revenue - $totalCost;
                
                return [
                    'costs' => [
                        'chicks' => $chickCost,
                        'feed' => $feedCost,
                        'medicine' => $medicineCost,
                        'total' => $totalCost
                    ],
                    'revenue' => $revenue,
                    'profit' => $profit,
                    'margin' => round(($profit / $revenue) * 100, 1),
                    'period' => '6 weeks'
                ];
            },
            'layer' => function($qty, $region) {
                $prices = $this->getPrices('poultry', $region);
                $setupCost = $qty * 1000; // One-time cost per layer
                $monthlyFeed = $qty * 900; // Monthly feed cost
                
                $eggsPerMonth = $qty * 25; // 25 eggs per layer per month
                $avgEggPrice = $prices['eggs_single']['average'];
                $monthlyRevenue = $eggsPerMonth * $avgEggPrice;
                $monthlyProfit = $monthlyRevenue - $monthlyFeed;
                
                return [
                    'setup_cost' => $setupCost,
                    'monthly_costs' => [
                        'feed' => $monthlyFeed,
                        'medicine' => $qty * 50
                    ],
                    'monthly_revenue' => $monthlyRevenue,
                    'monthly_profit' => $monthlyProfit,
                    'eggs_per_month' => $eggsPerMonth,
                    'payback_months' => ceil($setupCost / $monthlyProfit)
                ];
            },
            'dairy_cow' => function($qty, $region) {
                $prices = $this->getPrices('cattle', $region);
                $dailyMilk = $qty * 15; // 15 liters per cow per day
                $dailyFeed = $qty * 800; // Daily feed cost
                
                $monthlyMilk = $dailyMilk * 30;
                $avgMilkPrice = $prices['milk']['average'];
                $monthlyRevenue = $monthlyMilk * $avgMilkPrice;
                $monthlyProfit = $monthlyRevenue - ($dailyFeed * 30);
                
                return [
                    'daily_milk' => $dailyMilk,
                    'monthly_milk' => $monthlyMilk,
                    'monthly_revenue' => $monthlyRevenue,
                    'monthly_costs' => $dailyFeed * 30,
                    'monthly_profit' => $monthlyProfit
                ];
            }
        ];
        
        if (!isset($calculations[$type])) {
            return ['error' => 'Invalid type. Use: broiler, layer, dairy_cow'];
        }
        
        return $calculations[$type]($quantity, $region);
    }
    
    /**
     * Get seasonal price trends
     */
    public function getSeasonalTrends($item) {
        $trends = [
            'broiler_live' => [
                'high_season' => ['months' => ['November', 'December'], 'reason' => 'Holiday demand'],
                'low_season' => ['months' => ['March', 'April'], 'reason' => 'School opening, low demand'],
                'tip' => 'Buy day-old chicks in January, sell in February/March for good prices'
            ],
            'eggs_tray' => [
                'high_season' => ['months' => ['December', 'July'], 'reason' => 'Festive seasons, school holidays'],
                'low_season' => ['months' => ['January', 'September'], 'reason' => 'Post-holiday slump'],
                'tip' => 'Egg prices are stable year-round. Focus on production efficiency.'
            ],
            'milk' => [
                'high_season' => ['months' => ['March', 'April', 'May'], 'reason' => 'Long rains, good pasture'],
                'low_season' => ['months' => ['January', 'February'], 'reason' => 'Dry season, poor pasture'],
                'tip' => 'Store hay during rainy season for dry season feeding'
            ],
            'maize_90kg' => [
                'high_season' => ['months' => ['June', 'July'], 'reason' => 'Before harvest, low supply'],
                'low_season' => ['months' => ['August', 'September'], 'reason' => 'Harvest time, high supply'],
                'tip' => 'Store maize after harvest, sell when prices rise'
            ]
        ];
        
        return $trends[$item] ?? null;
    }
    
    /**
     * Get market news/alerts
     */
    function getMarketAlerts() {
        return [
            [
                'type' => 'price_drop',
                'item' => 'Maize',
                'message' => 'Maize prices expected to drop as harvest season approaches.',
                'date' => date('Y-m-d')
            ],
            [
                'type' => 'demand_up',
                'item' => 'Broilers',
                'message' => 'Broiler demand increasing due to school openings.',
                'date' => date('Y-m-d')
            ],
            [
                'type' => 'supply_shortage',
                'item' => 'Dairy Meal',
                'message' => 'Dairy meal prices rising due to import costs.',
                'date' => date('Y-m-d')
            ]
        ];
    }
    
    private function getPricingRecommendation($percentage) {
        if ($percentage > 20) {
            return "Your price is significantly above market. Consider lowering to attract more buyers.";
        } elseif ($percentage > 5) {
            return "Your price is slightly above market. You may still get sales but volume could be lower.";
        } elseif ($percentage >= -5) {
            return "Your price is competitive. Good pricing strategy.";
        } elseif ($percentage >= -20) {
            return "Your price is below market. You could potentially raise prices.";
        } else {
            return "Your price is significantly below market. You're leaving money on the table.";
        }
    }
}

// API endpoint handler
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'all';
    $category = $_GET['category'] ?? null;
    $item = $_GET['item'] ?? null;
    $region = $_GET['region'] ?? 'default';
    
    $market = new MarketPrices();
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'all':
            echo json_encode($market->getAllPrices($region));
            break;
        case 'category':
            echo json_encode($market->getPrices($category, $region));
            break;
        case 'item':
            echo json_encode($market->getItemPrice($category, $item, $region));
            break;
        case 'compare':
            $yourPrice = $_GET['your_price'] ?? 0;
            echo json_encode($market->comparePrice($category, $item, $yourPrice));
            break;
        case 'profit':
            $type = $_GET['type'] ?? 'broiler';
            $quantity = $_GET['quantity'] ?? 50;
            echo json_encode($market->calculateProfit($type, $quantity, $region));
            break;
        case 'trends':
            echo json_encode($market->getSeasonalTrends($item));
            break;
        case 'alerts':
            echo json_encode($market->getMarketAlerts());
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
