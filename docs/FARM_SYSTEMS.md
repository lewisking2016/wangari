# Wangari Farm Management System - New Features Documentation

## Overview

This document describes all the new systems and features added to enhance the Wangari Farm Management System.

---

## 1. 🤖 AI Chat Widget

**Location:** `Frontend/includes/ai_chat_widget.php`

### Features:
- **Floating Chat Button** - Always visible in bottom-right corner
- **Pre-loaded Knowledge Base** - Instant answers without internet
- **Multi-language Support** - English and Swahili
- **Voice Input** - Speak instead of type (great for low literacy)
- **Quick Actions** - One-tap access to common topics
- **WhatsApp Integration** - Direct link to WhatsApp for support

### How to Use:
1. Click the green chat button in bottom-right
2. Type your question or tap the microphone to speak
3. Use quick action buttons for common topics
4. Switch between English (EN) and Swahili (SW)

### Supported Topics:
- 🐔 Chicken feeding, health, housing
- 🐄 Cattle management
- 🐐 Goat care
- 🌾 Crop advice
- 💰 Financial calculations
- 📱 M-PESA guidance
- 🌤️ Weather advice
- 📈 Market prices

---

## 2. 🌤️ Weather Integration

**Location:** `Backend/config/weather.php`

### Features:
- Real-time weather data (requires API key)
- 5-day forecast
- Farm-specific weather alerts
- Seasonal farming calendar
- Weather-based advice

### API Endpoints:
```
GET /weather.php?action=current&city=Nairobi
GET /weather.php?action=forecast&city=Nairobi
GET /weather.php?action=advice&city=Nairobi
GET /weather.php?action=alerts&city=Nairobi
GET /weather.php?action=calendar&month=3
```

### Setup:
1. Get free API key from OpenWeatherMap
2. Add to environment: `OPENWEATHER_API_KEY=your_key_here`
3. System works with default data if no API key

---

## 3. 📈 Market Prices

**Location:** `Backend/config/market_prices.php`

### Features:
- Real-time market prices for Kenyan products
- Regional price variations
- Price comparison tool
- Profit calculators
- Seasonal trends
- Market alerts

### Categories:
- **Poultry** - Broilers, layers, eggs, day-old chicks
- **Cattle** - Milk, beef, calves, cows
- **Goats** - Males, females, kids
- **Crops** - Maize, beans, vegetables
- **Feeds** - All animal feed types
- **Inputs** - Farming inputs and equipment

### API Endpoints:
```
GET /market_prices.php?action=all&region=nairobi
GET /market_prices.php?action=category&category=poultry
GET /market_prices.php?action=item&category=poultry&item=broiler_live
GET /market_prices.php?action=compare&category=poultry&item=broiler_live&your_price=400
GET /market_prices.php?action=profit&type=broiler&quantity=50
GET /market_prices.php?action=trends&item=broiler_live
```

### Regional Multipliers:
- Nairobi: +10%
- Mombasa: +15%
- Kisumu: Base price
- Nakuru: -5%
- Eldoret: -10%

---

## 4. 📦 Inventory Alerts

**Location:** `Backend/config/inventory_alerts.php`

### Features:
- Low stock alerts (critical/warning/info)
- Reorder point tracking
- Supplier database
- Purchase order generation
- Stock history
- Days until stockout calculation

### Alert Types:
- **Critical** - Stock below minimum (order immediately!)
- **Warning** - Stock below reorder point (plan to order)
- **Info** - Stock at 50% capacity (monitor)

### Default Inventory Items:
#### Feeds:
- Broiler Starter/Grower/Finisher
- Layer Mash
- Dairy Meal
- Maize Bran

#### Medicines:
- Newcastle Vaccine
- Gumboro Vaccine
- Dewormer (Albendazole)
- Antibiotics (Oxytetracycline)

#### Equipment:
- Feed Troughs
- Drinking Cups
- Wood Shavings

### API Endpoints:
```
GET /inventory_alerts.php?action=alerts
GET /inventory_alerts.php?action=summary
GET /inventory_alerts.php?action=reorder
GET /inventory_alerts.php?action=suppliers&category=feeds
GET /inventory_alerts.php?action=stockout&item_key=broiler_starter
```

---

## 5. ⚡ Quick Actions

**Location:** `Frontend/includes/quick_actions.php`

### Features:
- Floating quick actions button
- One-tap access to common tasks
- Notification panel
- Offline indicator
- Toast notifications

### Quick Actions Available:
- Add Animal
- Record Vaccination
- Log Feeding
- Record Sale
- View Budget
- Check Inventory

### Notifications:
- Real-time alerts
- Stock warnings
- Weather alerts
- Sales confirmations

---

## 6. 📱 WhatsApp Integration

**Location:** `Backend/config/whatsapp.php`

### Features:
- Daily farm summaries via WhatsApp
- Stock alerts
- Vaccination reminders
- Weather alerts
- Sales notifications
- Payment confirmations
- Budget alerts
- Weekly reports

### WhatsApp Commands:
- `SUMMARY` - Get daily summary
- `STATUS` - Quick farm status
- `ALERTS` - View current alerts
- `ORDER` - Generate purchase order
- `HELP` - Show available commands

### Setup:
1. Create WhatsApp Business account
2. Get API key from Meta
3. Add to environment:
   ```
   WHATSAPP_API_KEY=your_key
   WHATSAPP_PHONE_NUMBER=your_number
   ```

---

## 7. 📊 Dashboard Widgets

**Location:** `Frontend/includes/farm_dashboard_widgets.php`

### Widgets Included:
1. **Weather Widget** - Current conditions + forecast
2. **Market Prices Widget** - Top products with price changes
3. **Inventory Alerts Widget** - Critical stock items
4. **Quick Stats Widget** - Today's summary
5. **Seasonal Calendar Widget** - Farming activities
6. **Profit Calculator Widget** - Quick profit estimates

---

## Installation Guide

### Step 1: Copy Files
```bash
# Copy all new files to your Wangari installation
cp -r Backend/config/farm_ai.php /path/to/wangari/Backend/config/
cp -r Backend/config/weather.php /path/to/wangari/Backend/config/
cp -r Backend/config/market_prices.php /path/to/wangari/Backend/config/
cp -r Backend/config/inventory_alerts.php /path/to/wangari/Backend/config/
cp -r Backend/config/whatsapp.php /path/to/wangari/Backend/config/
cp -r Backend/api/ai_chat.php /path/to/wangari/Backend/api/
cp -r Frontend/includes/ai_chat_widget.php /path/to/wangari/Frontend/includes/
cp -r Frontend/includes/quick_actions.php /path/to/wangari/Frontend/includes/
cp -r Frontend/includes/farm_dashboard_widgets.php /path/to/wangari/Frontend/includes/
```

### Step 2: Add to Pages
Add these includes to your pages:

```php
<!-- In your main layout file (before </body>) -->
<?php include 'Frontend/includes/ai_chat_widget.php'; ?>
<?php include 'Frontend/includes/quick_actions.php'; ?>

<!-- In dashboard.php -->
<?php include 'Frontend/includes/farm_dashboard_widgets.php'; ?>
```

### Step 3: Configure Environment
Add to your `.env` file:
```env
# Weather (optional - works without API)
OPENWEATHER_API_KEY=your_key_here

# WhatsApp (optional)
WHATSAPP_API_KEY=your_key_here
WHATSAPP_PHONE_NUMBER=your_number_here
```

### Step 4: Database Tables (Optional)
For full functionality, create these tables:

```sql
-- Inventory tracking
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_key VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    min_stock DECIMAL(10,2) DEFAULT 0,
    reorder_point DECIMAL(10,2) DEFAULT 0,
    max_stock DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20) NOT NULL,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(100),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_stock (current_stock)
);

-- Stock history
CREATE TABLE stock_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_key VARCHAR(50) NOT NULL,
    old_stock DECIMAL(10,2),
    new_stock DECIMAL(10,2),
    change_amount DECIMAL(10,2),
    reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item (item_key),
    INDEX idx_date (created_at)
);

-- Market prices history
CREATE TABLE market_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    item VARCHAR(50) NOT NULL,
    region VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_region (category, item, region)
);

-- Weather logs
CREATE TABLE weather_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    city VARCHAR(100) NOT NULL,
    temperature DECIMAL(5,2),
    humidity INT,
    description VARCHAR(100),
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_city_date (city, logged_at)
);
```

---

## Customization

### Adding New Inventory Items
Edit `Backend/config/inventory_alerts.php`:
```php
private $defaultInventory = [
    'your_category' => [
        'your_item' => [
            'name' => 'Your Item Name',
            'unit' => 'kg',
            'min_stock' => 10,
            'reorder_point' => 20,
            'max_stock' => 100,
            'current_stock' => 50,
            'unit_cost' => 100,
            'supplier' => 'Supplier Name',
            'lead_time_days' => 3
        ]
    ]
];
```

### Adding New Market Items
Edit `Backend/config/market_prices.php`:
```php
private $defaultPrices = [
    'your_category' => [
        'your_item' => [
            'min' => 100,
            'max' => 200,
            'unit' => 'per kg'
        ]
    ]
];
```

### Adding New AI Knowledge
Edit `Backend/config/farm_ai.php`:
```php
private function loadKnowledgeBase() {
    $this->knowledgeBase = [
        'your_topic' => [
            'subtopic' => [
                'key' => 'value'
            ]
        ]
    ];
}
```

---

## Troubleshooting

### Chat Widget Not Showing
1. Check if `ai_chat_widget.php` is included correctly
2. Verify CSS is not conflicting
3. Check browser console for errors

### Weather Not Loading
1. Verify API key is set correctly
2. Check internet connection
3. System works with default data if API fails

### Inventory Alerts Not Working
1. Check database connection
2. Verify table structure
3. Check for PHP errors in logs

### WhatsApp Not Sending
1. Verify API credentials
2. Check phone number format (+254...)
3. Ensure WhatsApp Business account is active

---

## Support

For issues or questions:
1. Check this documentation
2. Review code comments in each file
3. Contact support via WhatsApp widget

---

## Future Enhancements

Planned features:
- [ ] Push notifications for mobile
- [ ] SMS integration (Africa's Talking)
- [ ] Advanced analytics dashboard
- [ ] Multi-farm support
- [ ] Offline mode with sync
- [ ] Voice commands in Swahili
- [ ] Integration with M-PESA API
- [ ] Automated purchase orders
- [ ] Supplier price comparison
- [ ] Farm mapping/GPS integration

---

**Last Updated:** August 2026
**Version:** 2.0
