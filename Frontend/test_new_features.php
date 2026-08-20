<?php
/**
 * Test Page for New Farm Management Features
 * 
 * This page demonstrates:
 * - AI Chat Widget
 * - Weather Integration
 * - Market Prices
 * - Inventory Alerts
 * - Quick Actions
 * - Dashboard Widgets
 */

require_once __DIR__ . '/../Backend/config/farm_ai.php';
require_once __DIR__ . '/../Backend/config/weather.php';
require_once __DIR__ . '/../Backend/config/market_prices.php';
require_once __DIR__ . '/../Backend/config/inventory_alerts.php';

// Initialize services
$weather = new WeatherService();
$market = new MarketPrices();
$inventory = new InventoryAlerts();

// Get data
$currentWeather = $weather->getCurrentWeather('Nairobi');
$poultryPrices = $market->getPrices('poultry', 'nairobi');
$alerts = $inventory->getAlerts();
$summary = $inventory->getInventorySummary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wangari - New Features Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: #F8FAFC;
            padding: 24px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #1E293B;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #64748B;
            font-size: 14px;
        }
        
        .section {
            background: white;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: #F8FAFC;
            border-radius: 12px;
            padding: 16px;
        }
        
        .card-title {
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 12px;
        }
        
        .weather-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        
        .weather-temp {
            font-size: 32px;
            font-weight: 700;
            color: #1E293B;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E2E8F0;
        }
        
        .price-item:last-child {
            border-bottom: none;
        }
        
        .alert-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .alert-item.critical {
            background: #FEF2F2;
            border-color: #EF4444;
        }
        
        .alert-item.warning {
            background: #FFFBEB;
            border-color: #F59E0B;
        }
        
        .alert-item.info {
            background: #EFF6FF;
            border-color: #3B82F6;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        
        .stat-card {
            text-align: center;
            padding: 16px;
            background: #F8FAFC;
            border-radius: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1E293B;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748B;
            margin-top: 4px;
        }
        
        .instructions {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .instructions h3 {
            color: #16A34A;
            margin-bottom: 12px;
        }
        
        .instructions ul {
            margin-left: 20px;
            color: #475569;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌾 Wangari Farm Management - New Features</h1>
            <p>Complete farm management system with AI assistant, weather, market prices, and inventory alerts</p>
        </div>
        
        <!-- Weather Section -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-cloud-sun" style="color: #F59E0B;"></i>
                Weather Integration
            </h2>
            <div class="grid">
                <div class="card">
                    <div class="weather-icon">⛅</div>
                    <div class="weather-temp"><?= $currentWeather['temperature'] ?>°C</div>
                    <div style="color: #64748B; text-transform: capitalize;"><?= $currentWeather['description'] ?></div>
                    <div style="margin-top: 12px; font-size: 14px; color: #475569;">
                        <div>💧 Humidity: <?= $currentWeather['humidity'] ?>%</div>
                        <div>💨 Wind: <?= $currentWeather['wind_speed'] ?> km/h</div>
                        <div>🌅 Sunrise: <?= $currentWeather['sunrise'] ?></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">Farming Advice</div>
                    <?php 
                    $advice = $weather->getFarmingAdvice($currentWeather);
                    if (!empty($advice)): ?>
                        <?php foreach ($advice as $tip): ?>
                            <div style="margin-bottom: 8px;">
                                <?= $tip['icon'] ?> <?= $tip['message'] ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div>✅ Weather conditions are good for farming today.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Market Prices Section -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-chart-line" style="color: #3B82F6;"></i>
                Market Prices (Nairobi)
            </h2>
            <div class="grid">
                <?php foreach (array_slice($poultryPrices, 0, 6) as $item => $price): ?>
                    <div class="card">
                        <div class="card-title"><?= ucwords(str_replace('_', ' ', $item)) ?></div>
                        <div class="price-item">
                            <span>Min:</span>
                            <span style="font-weight: 600;">KSh <?= number_format($price['min']) ?></span>
                        </div>
                        <div class="price-item">
                            <span>Max:</span>
                            <span style="font-weight: 600;">KSh <?= number_format($price['max']) ?></span>
                        </div>
                        <div class="price-item">
                            <span>Average:</span>
                            <span style="font-weight: 600; color: #16A34A;">KSh <?= number_format($price['average']) ?></span>
                        </div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 8px;">
                            <?= $price['unit'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Inventory Alerts Section -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-exclamation-triangle" style="color: #F59E0B;"></i>
                Inventory Alerts
            </h2>
            
            <div class="stat-grid" style="margin-bottom: 20px;">
                <div class="stat-card">
                    <div class="stat-value" style="color: #EF4444;"><?= $summary['critical'] ?></div>
                    <div class="stat-label">Critical</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #F59E0B;"><?= $summary['warning'] ?></div>
                    <div class="stat-label">Warning</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #16A34A;"><?= $summary['good'] ?></div>
                    <div class="stat-label">Good</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">KSh <?= number_format($summary['total_value']) ?></div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>
            
            <?php foreach (array_slice($alerts, 0, 5) as $alert): ?>
                <div class="alert-item <?= $alert['type'] ?>">
                    <span><?= $alert['type'] === 'critical' ? '🚨' : ($alert['type'] === 'warning' ? '⚠️' : 'ℹ️') ?></span>
                    <div>
                        <div style="font-weight: 500;"><?= $alert['name'] ?></div>
                        <div style="font-size: 13px; color: #64748B;"><?= $alert['message'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Instructions -->
        <div class="instructions">
            <h3>🚀 How to Use New Features</h3>
            <ul>
                <li><strong>AI Chat Widget:</strong> Click the green chat button in bottom-right corner</li>
                <li><strong>Quick Actions:</strong> Click the blue lightning bolt button for common tasks</li>
                <li><strong>Notifications:</strong> Click the bell icon in the header for alerts</li>
                <li><strong>Voice Input:</strong> In the chat widget, click the microphone to speak</li>
                <li><strong>Swahili Support:</strong> Switch language in the chat widget header</li>
                <li><strong>WhatsApp:</strong> Click the WhatsApp button in chat widget for direct support</li>
                <li><strong>Offline Mode:</strong> System works without internet for basic features</li>
            </ul>
        </div>
    </div>
    
    <!-- Include AI Chat Widget -->
    <?php include 'Frontend/includes/ai_chat_widget.php'; ?>
    
    <!-- Include Quick Actions -->
    <?php include 'Frontend/includes/quick_actions.php'; ?>
</body>
</html>
