<!-- Farm Dashboard Widgets -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Widget Container */
.farm-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

/* Base Widget */
.farm-widget {
    background: white;
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.farm-widget:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.widget-header {
    padding: 16px 20px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.widget-title {
    font-weight: 600;
    font-size: 15px;
    color: #1E293B;
    display: flex;
    align-items: center;
    gap: 8px;
}

.widget-action {
    font-size: 13px;
    color: #3B82F6;
    text-decoration: none;
    font-weight: 500;
}

.widget-action:hover {
    text-decoration: underline;
}

.widget-body {
    padding: 20px;
}

/* Weather Widget */
.weather-widget .weather-main {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 16px;
}

.weather-icon {
    font-size: 48px;
}

.weather-temp {
    font-size: 36px;
    font-weight: 700;
    color: #1E293B;
}

.weather-desc {
    font-size: 14px;
    color: #64748B;
    text-transform: capitalize;
}

.weather-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid #E2E8F0;
}

.weather-detail {
    text-align: center;
}

.weather-detail-value {
    font-weight: 600;
    color: #1E293B;
}

.weather-detail-label {
    font-size: 12px;
    color: #64748B;
}

/* Market Prices Widget */
.prices-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F8FAFC;
    border-radius: 10px;
}

.price-name {
    font-weight: 500;
    color: #1E293B;
}

.price-value {
    font-weight: 600;
    color: #16A34A;
}

.price-change {
    font-size: 12px;
    margin-left: 8px;
}

.price-change.up { color: #16A34A; }
.price-change.down { color: #EF4444; }

/* Inventory Alerts Widget */
.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.alert-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
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

.alert-icon {
    font-size: 20px;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 500;
    font-size: 14px;
    color: #1E293B;
}

.alert-message {
    font-size: 13px;
    color: #64748B;
    margin-top: 4px;
}

/* Quick Stats Widget */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.stat-card {
    text-align: center;
    padding: 16px;
    background: #F8FAFC;
    border-radius: 12px;
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 8px;
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

/* Seasonal Calendar Widget */
.calendar-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.calendar-season {
    font-weight: 600;
    color: #16A34A;
    font-size: 14px;
    margin-bottom: 8px;
}

.calendar-activities {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.calendar-activity {
    font-size: 13px;
    color: #475569;
    padding: 8px 12px;
    background: #F8FAFC;
    border-radius: 8px;
}

/* Profit Calculator Widget */
.calculator-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.calculator-input {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.calculator-input label {
    font-size: 13px;
    font-weight: 500;
    color: #475569;
}

.calculator-input select,
.calculator-input input {
    padding: 10px 12px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    font-size: 14px;
}

.calculator-btn {
    padding: 12px;
    background: #16A34A;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.calculator-btn:hover {
    background: #15803D;
}

.calculator-result {
    margin-top: 12px;
    padding: 16px;
    background: #F0FDF4;
    border-radius: 10px;
    display: none;
}

.calculator-result.show {
    display: block;
}

.result-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.result-label {
    color: #475569;
}

.result-value {
    font-weight: 600;
    color: #1E293B;
}

.result-profit {
    font-size: 18px;
    color: #16A34A;
    border-top: 1px solid #BBF7D0;
    padding-top: 8px;
    margin-top: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .farm-widgets {
        grid-template-columns: 1fr;
    }
    
    .weather-details {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- Widget Grid -->
<div class="farm-widgets">
    
    <!-- Weather Widget -->
    <div class="farm-widget weather-widget">
        <div class="widget-header">
            <span class="widget-title">
                <i class="fas fa-cloud-sun" style="color: #F59E0B;"></i>
                Weather
            </span>
            <a href="#" class="widget-action">Full Forecast</a>
        </div>
        <div class="widget-body">
            <div class="weather-main">
                <span class="weather-icon">⛅</span>
                <div>
                    <div class="weather-temp">24°C</div>
                    <div class="weather-desc">Partly Cloudy</div>
                </div>
            </div>
            <div class="weather-details">
                <div class="weather-detail">
                    <div class="weather-detail-value">65%</div>
                    <div class="weather-detail-label">Humidity</div>
                </div>
                <div class="weather-detail">
                    <div class="weather-detail-value">12 km/h</div>
                    <div class="weather-detail-label">Wind</div>
                </div>
                <div class="weather-detail">
                    <div class="weather-detail-value">20%</div>
                    <div class="weather-detail-label">Rain Chance</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Market Prices Widget -->
    <div class="farm-widget">
        <div class="widget-header">
            <span class="widget-title">
                <i class="fas fa-chart-line" style="color: #3B82F6;"></i>
                Market Prices
            </span>
            <a href="#" class="widget-action">View All</a>
        </div>
        <div class="widget-body">
            <div class="prices-list">
                <div class="price-item">
                    <span class="price-name">Broiler (live)</span>
                    <span>
                        <span class="price-value">KSh 420</span>
                        <span class="price-change up">↑ 5%</span>
                    </span>
                </div>
                <div class="price-item">
                    <span class="price-name">Eggs (tray)</span>
                    <span>
                        <span class="price-value">KSh 450</span>
                        <span class="price-change down">↓ 2%</span>
                    </span>
                </div>
                <div class="price-item">
                    <span class="price-name">Milk (liter)</span>
                    <span>
                        <span class="price-value">KSh 50</span>
                        <span class="price-change up">↑ 3%</span>
                    </span>
                </div>
                <div class="price-item">
                    <span class="price-name">Maize (90kg)</span>
                    <span>
                        <span class="price-value">KSh 3,800</span>
                        <span class="price-change down">↓ 8%</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Alerts Widget -->
    <div class="farm-widget">
        <div class="widget-header">
            <span class="widget-title">
                <i class="fas fa-exclamation-triangle" style="color: #F59E0B;"></i>
                Inventory Alerts
            </span>
            <a href="#" class="widget-action">Manage Stock</a>
        </div>
        <div class="widget-body">
            <div class="alerts-list">
                <div class="alert-item critical">
                    <span class="alert-icon">🚨</span>
                    <div class="alert-content">
                        <div class="alert-title">Low Stock: Starter Feed</div>
                        <div class="alert-message">Only 15 kg remaining. Reorder immediately!</div>
                    </div>
                </div>
                <div class="alert-item warning">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-content">
                        <div class="alert-title">Reorder: Dewormer</div>
                        <div class="alert-message">50 tablets left. Order within 3 days.</div>
                    </div>
                </div>
                <div class="alert-item info">
                    <span class="alert-icon">ℹ️</span>
                    <div class="alert-content">
                        <div class="alert-title">Stock Level: Layers Mash</div>
                        <div class="alert-message">200 kg available. Good for 2 weeks.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats Widget -->
    <div class="farm-widget">
        <div class="widget-header">
            <span class="widget-title">
                <i class="fas fa-chart-pie" style="color: #8B5CF6;"></i>
                Today's Summary
            </span>
        </div>
        <div class="widget-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🥚</div>
                    <div class="stat-value">45</div>
                    <div class="stat-label">Eggs Collected</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🐔</div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Mortality</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">KSh 8,500</div>
                    <div class="stat-label">Sales Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-value">KSh 4,200</div>
                    <div class="stat-label">Profit Today</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Seasonal Calendar Widget -->
    <div class="farm-widget">
        <div class="widget-header">
            <span class="widget-title">
                <i class="fas fa-calendar-alt" style="color: #16A34A;"></i>
                Farming Calendar
            </span>
        </div>
        <div class="widget-body">
            <div class="calendar-content">
                <div class="calendar-season">🌧️ Long Rains Season</div>
                <div class="calendar-activities">
                    <div class="calendar-activity">🌾 Plant maize, beans, vegetables</div>
                    <div class="calendar-activity">🐔 Watch for coccidiosis (wet conditions)</div>
                    <div class="calendar-activity">💧 Ensure drainage is clear</div>
                    <div class="calendar-activity">🌱 Transplant seedlings</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profit Calculator Widget -->
    <div class="farm-widget">
        <div class="widget-header">
            <span class="widget-title">
                <i class="fas fa-calculator" style="color: #0EA5E9;"></i>
                Profit Calculator
            </span>
        </div>
        <div class="widget-body">
            <div class="calculator-form">
                <div class="calculator-input">
                    <label>Type</label>
                    <select id="calcType">
                        <option value="broiler">Broilers</option>
                        <option value="layer">Layers</option>
                        <option value="dairy_cow">Dairy Cow</option>
                    </select>
                </div>
                <div class="calculator-input">
                    <label>Quantity</label>
                    <input type="number" id="calcQuantity" value="50" min="1">
                </div>
                <button class="calculator-btn" onclick="calculateProfit()">Calculate Profit</button>
                <div class="calculator-result" id="calcResult">
                    <div class="result-row">
                        <span class="result-label">Total Cost:</span>
                        <span class="result-value" id="resultCost">-</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Expected Revenue:</span>
                        <span class="result-value" id="resultRevenue">-</span>
                    </div>
                    <div class="result-row result-profit">
                        <span class="result-label">Net Profit:</span>
                        <span class="result-value" id="resultProfit">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
// Profit Calculator
function calculateProfit() {
    const type = document.getElementById('calcType').value;
    const quantity = parseInt(document.getElementById('calcQuantity').value);
    
    // Profit calculations
    const calculations = {
        broiler: {
            cost: quantity * 340, // avg cost per broiler
            revenue: quantity * 420 // avg selling price
        },
        layer: {
            cost: quantity * 1000, // setup cost
            revenue: quantity * 25 * 15 * 6 // 6 months of eggs
        },
        dairy_cow: {
            cost: quantity * 500 * 30, // monthly feed cost
            revenue: quantity * 15 * 50 * 30 // monthly milk revenue
        }
    };
    
    const calc = calculations[type];
    const profit = calc.revenue - calc.cost;
    
    document.getElementById('resultCost').textContent = 'KSh ' + calc.cost.toLocaleString();
    document.getElementById('resultRevenue').textContent = 'KSh ' + calc.revenue.toLocaleString();
    document.getElementById('resultProfit').textContent = 'KSh ' + profit.toLocaleString();
    document.getElementById('calcResult').classList.add('show');
}

// Weather Widget Update (simulated)
function updateWeather() {
    // This would normally fetch from weather API
    console.log('Weather updated');
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateWeather();
});
</script>
