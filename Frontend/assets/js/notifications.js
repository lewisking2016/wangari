/**
 * Wangari Push Notifications
 * Handles browser notifications for reminders and alerts
 */

class WangariNotifications {
    constructor() {
        this.permission = 'default';
        this.init();
    }

    async init() {
        // Request permission on first interaction
        if ('Notification' in window) {
            this.permission = Notification.permission;
            if (this.permission === 'default') {
                // Will request on user click
            }
        }
    }

    async requestPermission() {
        if (!('Notification' in window)) {
            console.log('Notifications not supported');
            return false;
        }

        const permission = await Notification.requestPermission();
        this.permission = permission;
        return permission === 'granted';
    }

    async showNotification(title, options = {}) {
        if (this.permission !== 'granted') {
            const granted = await this.requestPermission();
            if (!granted) return false;
        }

        const defaultOptions = {
            icon: '/Frontend/images/wangari-logo.png',
            badge: '/Frontend/images/wangari-logo.png',
            vibrate: [200, 100, 200],
            tag: 'wangari-notification',
            renotify: true,
            ...options
        };

        try {
            new Notification(title, defaultOptions);
            return true;
        } catch (e) {
            console.error('Notification failed:', e);
            return false;
        }
    }

    // Reminder notifications
    async notifyReminder(reminder) {
        return this.showNotification(`Reminder: ${reminder.title}`, {
            body: reminder.description || 'You have a pending reminder',
            tag: `reminder-${reminder.id}`,
            data: { type: 'reminder', id: reminder.id }
        });
    }

    // Task notifications
    async notifyTask(task) {
        return this.showNotification(`Task: ${task.title}`, {
            body: `Priority: ${task.priority} | Due: ${task.due_time || 'Today'}`,
            tag: `task-${task.id}`,
            data: { type: 'task', id: task.id }
        });
    }

    // Alert notifications
    async notifyAlert(alert) {
        return this.showNotification(`Alert: ${alert.title}`, {
            body: alert.message || 'Action required',
            tag: `alert-${alert.id}`,
            requireInteraction: true,
            data: { type: 'alert', id: alert.id }
        });
    }

    // Weather notifications
    async notifyWeather(weather) {
        return this.showNotification('Weather Update', {
            body: `${weather.condition} - ${weather.temperature}°C`,
            tag: 'weather',
            data: { type: 'weather' }
        });
    }

    // Check for reminders periodically
    async checkReminders() {
        try {
            const response = await fetch('/Backend/api/worker_api.php?action=check_reminders');
            const data = await response.json();
            
            if (data.success && data.reminders) {
                for (const reminder of data.reminders) {
                    await this.notifyReminder(reminder);
                }
            }
        } catch (e) {
            console.error('Failed to check reminders:', e);
        }
    }

    // Check for tasks periodically
    async checkTasks() {
        try {
            const response = await fetch('/Backend/api/worker_api.php?action=check_tasks');
            const data = await response.json();
            
            if (data.success && data.tasks) {
                for (const task of data.tasks) {
                    await this.notifyTask(task);
                }
            }
        } catch (e) {
            console.error('Failed to check tasks:', e);
        }
    }

    // Start periodic checks
    startPeriodicChecks(intervalMinutes = 5) {
        // Check immediately
        this.checkReminders();
        this.checkTasks();

        // Then periodically
        setInterval(() => {
            this.checkReminders();
            this.checkTasks();
        }, intervalMinutes * 60 * 1000);
    }
}

// Initialize globally
const wangariNotifications = new WangariNotifications();

// Auto-start periodic checks if user is logged in
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('admin-layout') || 
        document.querySelector('.worker-dashboard')) {
        wangariNotifications.startPeriodicChecks(5);
    }
});

// Request permission on first click
document.addEventListener('click', async () => {
    if (wangariNotifications.permission === 'default') {
        await wangariNotifications.requestPermission();
    }
}, { once: true });
