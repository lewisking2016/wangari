<!-- Quick Actions & Notifications Panel -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Quick Actions Panel */
.quick-actions-panel {
    position: fixed;
    bottom: 100px;
    right: 100px;
    z-index: 9998;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.quick-actions-fab {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: white;
    font-size: 24px;
}

.quick-actions-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 30px rgba(59, 130, 246, 0.5);
}

.quick-actions-menu {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 280px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 60px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.quick-actions-menu.open {
    display: flex;
}

.quick-actions-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    color: white;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quick-actions-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
}

.quick-actions-list {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.quick-action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.2s;
    color: #334155;
    text-decoration: none;
}

.quick-action-item:hover {
    background: #F1F5F9;
}

.quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.quick-action-icon.green { background: #DCFCE7; }
.quick-action-icon.blue { background: #DBEAFE; }
.quick-action-icon.purple { background: #F3E8FF; }
.quick-action-icon.orange { background: #FED7AA; }
.quick-action-icon.red { background: #FEE2E2; }

.quick-action-text {
    flex: 1;
}

.quick-action-title {
    font-weight: 500;
    font-size: 14px;
}

.quick-action-desc {
    font-size: 12px;
    color: #64748B;
}

/* Notifications Panel */
.notifications-panel {
    position: fixed;
    top: 80px;
    right: 24px;
    width: 380px;
    max-height: 500px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 60px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    z-index: 9997;
    border: 1px solid rgba(0, 0, 0, 0.08);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notifications-panel.open {
    display: flex;
}

.notifications-header {
    padding: 16px 20px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-title {
    font-weight: 600;
    font-size: 16px;
    color: #1E293B;
}

.notifications-count {
    background: #EF4444;
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
}

.notifications-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 8px;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #F8FAFC;
}

.notification-item.unread {
    background: #F0F9FF;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.notification-icon.warning { background: #FEF3C7; }
.notification-icon.danger { background: #FEE2E2; }
.notification-icon.success { background: #DCFCE7; }
.notification-icon.info { background: #DBEAFE; }

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 500;
    font-size: 14px;
    color: #1E293B;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 13px;
    color: #64748B;
    line-height: 1.4;
}

.notification-time {
    font-size: 12px;
    color: #94A3B8;
    margin-top: 4px;
}

/* Offline Indicator */
.offline-indicator {
    position: fixed;
    bottom: 24px;
    left: 24px;
    background: #FEE2E2;
    color: #DC2626;
    padding: 12px 20px;
    border-radius: 12px;
    display: none;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    z-index: 9996;
}

.offline-indicator.show {
    display: flex;
}

.offline-indicator i {
    font-size: 18px;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 100px;
    right: 24px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.toast {
    background: white;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(100px); }
    to { opacity: 1; transform: translateX(0); }
}

.toast.success { border-left: 4px solid #22C55E; }
.toast.warning { border-left: 4px solid #F59E0B; }
.toast.error { border-left: 4px solid #EF4444; }
.toast.info { border-left: 4px solid #3B82F6; }

.toast-icon {
    font-size: 20px;
}

.toast-message {
    flex: 1;
    font-size: 14px;
    color: #1E293B;
}

.toast-close {
    background: none;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    font-size: 16px;
}

/* Notification Bell */
.notification-bell {
    position: relative;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
}

.notification-bell i {
    font-size: 20px;
    color: #64748B;
}

.notification-bell .badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #EF4444;
    color: white;
    font-size: 10px;
    font-weight: 600;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mobile Responsive */
@media (max-width: 480px) {
    .quick-actions-panel {
        bottom: 100px;
        right: 80px;
    }
    
    .quick-actions-menu {
        width: 260px;
    }
    
    .notifications-panel {
        width: calc(100% - 48px);
        right: 24px;
    }
}
</style>

<!-- Quick Actions Panel -->
<div class="quick-actions-panel">
    <div class="quick-actions-menu" id="quickActionsMenu">
        <div class="quick-actions-header">
            <span>Quick Actions</span>
            <button class="quick-actions-close" onclick="toggleQuickActions()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="quick-actions-list">
            <a href="hub_operations.php?tab=animals" class="quick-action-item">
                <div class="quick-action-icon green">🐄</div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Add Animal</div>
                    <div class="quick-action-desc">Register new livestock</div>
                </div>
            </a>
            <a href="hub_operations.php?tab=vaccinations" class="quick-action-item">
                <div class="quick-action-icon blue">💉</div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Record Vaccination</div>
                    <div class="quick-action-desc">Log vaccination event</div>
                </div>
            </a>
            <a href="hub_operations.php?tab=feeding" class="quick-action-item">
                <div class="quick-action-icon purple">🍽️</div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Log Feeding</div>
                    <div class="quick-action-desc">Record feed distribution</div>
                </div>
            </a>
            <a href="hub_operations.php?tab=sales" class="quick-action-item">
                <div class="quick-action-icon orange">💰</div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Record Sale</div>
                    <div class="quick-action-desc">Log a new sale</div>
                </div>
            </a>
            <a href="hub_operations.php?tab=budgets" class="quick-action-item">
                <div class="quick-action-icon green">📊</div>
                <div class="quick-action-text">
                    <div class="quick-action-title">View Budget</div>
                    <div class="quick-action-desc">Check budget status</div>
                </div>
            </a>
            <a href="hub_operations.php?tab=inventory" class="quick-action-item">
                <div class="quick-action-icon red">📦</div>
                <div class="quick-action-text">
                    <div class="quick-action-title">Check Inventory</div>
                    <div class="quick-action-desc">View stock levels</div>
                </div>
            </a>
        </div>
    </div>
    <button class="quick-actions-fab" onclick="toggleQuickActions()">
        <i class="fas fa-bolt"></i>
    </button>
</div>

<!-- Notifications Panel -->
<div class="notifications-panel" id="notificationsPanel">
    <div class="notifications-header">
        <span class="notifications-title">Notifications</span>
        <span class="notifications-count" id="notifCount">3</span>
    </div>
    <div class="notifications-list" id="notificationsList">
        <div class="notification-item unread">
            <div class="notification-icon warning">⚠️</div>
            <div class="notification-content">
                <div class="notification-title">Low Stock Alert</div>
                <div class="notification-message">Broiler Starter Feed is running low. Only 150 kg remaining.</div>
                <div class="notification-time">2 hours ago</div>
            </div>
        </div>
        <div class="notification-item unread">
            <div class="notification-icon danger">🚨</div>
            <div class="notification-content">
                <div class="notification-title">Vaccination Due</div>
                <div class="notification-message">50 broilers need Newcastle vaccination today.</div>
                <div class="notification-time">5 hours ago</div>
            </div>
        </div>
        <div class="notification-item">
            <div class="notification-icon success">✅</div>
            <div class="notification-content">
                <div class="notification-title">Sale Recorded</div>
                <div class="notification-message">Sold 30 broilers for KSh 13,500</div>
                <div class="notification-time">Yesterday</div>
            </div>
        </div>
        <div class="notification-item">
            <div class="notification-icon info">ℹ️</div>
            <div class="notification-content">
                <div class="notification-title">Weather Alert</div>
                <div class="notification-message">Heavy rain expected tomorrow. Ensure drainage is clear.</div>
                <div class="notification-time">Yesterday</div>
            </div>
        </div>
    </div>
</div>

<!-- Offline Indicator -->
<div class="offline-indicator" id="offlineIndicator">
    <i class="fas fa-wifi-slash"></i>
    <span>You're offline. Some features may be limited.</span>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Header Notification Bell -->
<div class="notification-bell" onclick="toggleNotifications()" id="notificationBell">
    <i class="fas fa-bell"></i>
    <span class="badge" id="notifBadge">3</span>
</div>

<script>
// Quick Actions
function toggleQuickActions() {
    const menu = document.getElementById('quickActionsMenu');
    menu.classList.toggle('open');
}

// Notifications
function toggleNotifications() {
    const panel = document.getElementById('notificationsPanel');
    panel.classList.toggle('open');
}

// Close panels when clicking outside
document.addEventListener('click', (e) => {
    const quickActions = document.querySelector('.quick-actions-panel');
    const notifications = document.querySelector('.notifications-panel');
    
    if (!quickActions.contains(e.target)) {
        document.getElementById('quickActionsMenu').classList.remove('open');
    }
    
    if (!notifications.contains(e.target) && !document.getElementById('notificationBell').contains(e.target)) {
        document.getElementById('notificationsPanel').classList.remove('open');
    }
});

// Offline Detection
window.addEventListener('online', () => {
    document.getElementById('offlineIndicator').classList.remove('show');
    showToast('success', 'Back Online', 'You\'re connected to the internet.');
});

window.addEventListener('offline', () => {
    document.getElementById('offlineIndicator').classList.add('show');
    showToast('warning', 'Offline Mode', 'You\'re currently offline.');
});

// Check initial state
if (!navigator.onLine) {
    document.getElementById('offlineIndicator').classList.add('show');
}

// Toast Notifications
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✅',
        warning: '⚠️',
        error: '❌',
        info: 'ℹ️'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type]}</span>
        <div class="toast-message">
            <strong>${title}</strong><br>
            ${message}
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Example: Show a welcome toast on page load
document.addEventListener('DOMContentLoaded', () => {
    // Check for low stock alerts
    checkInventoryAlerts();
});

function checkInventoryAlerts() {
    // This would normally fetch from the API
    // For demo, we'll show a sample notification
    setTimeout(() => {
        showToast('warning', 'Stock Alert', 'Broiler Starter Feed is below reorder point.');
    }, 3000);
}

// Export functions for global use
window.showToast = showToast;
window.toggleQuickActions = toggleQuickActions;
window.toggleNotifications = toggleNotifications;
</script>
