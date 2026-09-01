/**
 * Wangari Offline Sync Manager
 * 
 * Handles offline data entry, sync queue, and Service Worker communication.
 * Farmers can enter data without internet — it syncs automatically when they reconnect.
 * 
 * Usage in any page:
 *   <script src="/Frontend/assets/js/offline-sync.js"></script>
 *   WangariOffline.logProduction({ eggs: 40, mortality: 2 })
 *     .then(response => console.log(response));
 */

const WangariOffline = (() => {
    const DB_NAME = 'WangariOffline';
    const DB_VERSION = 1;
    let db = null;
    let swRegistration = null;
    
    // ═══════ INITIALIZATION ═══════
    
    async function init() {
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            try {
                swRegistration = await navigator.serviceWorker.register('/Frontend/assets/js/service-worker.js');
                console.log('Wangari SW registered');
                
                // Listen for SW messages
                navigator.serviceWorker.addEventListener('message', (event) => {
                    console.log('SW message:', event.data);
                });
            } catch (e) {
                console.error('SW registration failed:', e);
            }
        }
        
        // Open IndexedDB
        db = await openDB();
        
        // Set up online/offline listeners
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);
        
        // Show connection status
        updateConnectionStatus();
        
        console.log('WangariOffline initialized');
    }
    
    // ═══════ DATABASE ═══════
    
    function openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            
            request.onupgradeneeded = (event) => {
                const database = event.target.result;
                
                if (!database.objectStoreNames.contains('syncQueue')) {
                    const store = database.createObjectStore('syncQueue', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('timestamp', 'timestamp', { unique: false });
                }
                
                if (!database.objectStoreNames.contains('production')) {
                    database.createObjectStore('production', { 
                        keyPath: ['user_id', 'record_date'] 
                    });
                }
                
                if (!database.objectStoreNames.contains('expenses')) {
                    database.createObjectStore('expenses', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                }
                
                if (!database.objectStoreNames.contains('income')) {
                    database.createObjectStore('income', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                }
                
                if (!database.objectStoreNames.contains('inventory')) {
                    database.createObjectStore('inventory', { 
                        keyPath: 'id' 
                    });
                }
            };
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    // ═══════ DATA ENTRY FUNCTIONS ═══════
    
    /**
     * Log production data (eggs, mortality, milk)
     * Works offline — stores locally and queues for sync
     */
    async function logProduction(data) {
        const today = new Date().toISOString().split('T')[0];
        const userId = getUserId();
        
        const record = {
            user_id: userId,
            record_date: today,
            eggs_collected: data.eggs || 0,
            mortality: data.mortality || 0,
            milk_litres: data.milk || 0,
            timestamp: Date.now(),
            synced: false
        };
        
        // Store locally
        const tx = db.transaction('production', 'readwrite');
        tx.objectStore('production').put(record);
        
        // Queue for sync
        await queueForSync({
            url: '/Backend/api/whatsapp_bot.php',
            payload: { phone: getUserPhone(), message: buildProductionMessage(data) }
        });
        
        // Show offline indicator if needed
        if (!navigator.onLine) {
            showOfflineToast('Data saved locally. Will sync when online.');
        }
        
        return {
            success: true,
            offline: !navigator.onLine,
            record: record
        };
    }
    
    /**
     * Log an expense
     */
    async function logExpense(data) {
        const today = new Date().toISOString().split('T')[0];
        
        const record = {
            user_id: getUserId(),
            expense_date: today,
            category: data.category || 'misc',
            description: data.description || '',
            amount: data.amount || 0,
            timestamp: Date.now(),
            synced: false
        };
        
        const tx = db.transaction('expenses', 'readwrite');
        tx.objectStore('expenses').add(record);
        
        await queueForSync({
            url: '/Backend/api/whatsapp_bot.php',
            payload: { 
                phone: getUserPhone(), 
                message: `expense ${record.amount} ${record.description}` 
            }
        });
        
        if (!navigator.onLine) {
            showOfflineToast('Expense saved locally.');
        }
        
        return { success: true, offline: !navigator.onLine, record };
    }
    
    /**
     * Log a sale/income
     */
    async function logSale(data) {
        const today = new Date().toISOString().split('T')[0];
        
        const record = {
            user_id: getUserId(),
            income_date: today,
            category: 'sales',
            description: `${data.quantity} ${data.unit || 'crates'} @ KES ${data.price}`,
            amount: (data.quantity || 0) * (data.price || 0),
            customer_name: data.customer || null,
            timestamp: Date.now(),
            synced: false
        };
        
        const tx = db.transaction('income', 'readwrite');
        tx.objectStore('income').add(record);
        
        await queueForSync({
            url: '/Backend/api/whatsapp_bot.php',
            payload: { 
                phone: getUserPhone(), 
                message: `sold ${data.quantity} ${data.unit || 'crates'} @ ${data.price}${data.customer ? ' ' + data.customer : ''}` 
            }
        });
        
        if (!navigator.onLine) {
            showOfflineToast('Sale saved locally.');
        }
        
        return { success: true, offline: !navigator.onLine, record };
    }
    
    // ═══════ SYNC QUEUE ═══════
    
    async function queueForSync(data) {
        const tx = db.transaction('syncQueue', 'readwrite');
        const store = tx.objectStore('syncQueue');
        
        store.add({
            url: data.url,
            payload: data.payload,
            timestamp: Date.now(),
            status: 'pending',
            retries: 0
        });
        
        // Try immediate sync if online
        if (navigator.onLine) {
            await processSyncQueue();
        } else {
            // Register background sync
            if (swRegistration && swRegistration.sync) {
                swRegistration.sync.register('wangari-sync');
            }
        }
    }
    
    async function processSyncQueue() {
        if (!navigator.onLine) return;
        
        const tx = db.transaction('syncQueue', 'readwrite');
        const store = tx.objectStore('syncQueue');
        const request = store.getAll();
        
        return new Promise((resolve) => {
            request.onsuccess = async () => {
                const items = request.result;
                let syncedCount = 0;
                
                for (const item of items) {
                    if (item.status === 'synced') continue;
                    
                    try {
                        const response = await fetch(item.url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(item.payload)
                        });
                        
                        if (response.ok) {
                            store.delete(item.id);
                            syncedCount++;
                        } else {
                            // Increment retry count
                            item.retries = (item.retries || 0) + 1;
                            if (item.retries >= 5) {
                                item.status = 'failed';
                            }
                            store.put(item);
                        }
                    } catch (e) {
                        // Will retry on next sync
                        item.retries = (item.retries || 0) + 1;
                        store.put(item);
                    }
                }
                
                if (syncedCount > 0) {
                    showOfflineToast(`✅ ${syncedCount} items synced!`);
                }
                
                resolve(syncedCount);
            };
            request.onerror = () => resolve(0);
        });
    }
    
    // ═══════ GET PENDING COUNT ═══════
    
    async function getPendingCount() {
        if (!db) return 0;
        
        const tx = db.transaction('syncQueue', 'readonly');
        const store = tx.objectStore('syncQueue');
        const index = store.index('status');
        const request = index.count('pending');
        
        return new Promise((resolve) => {
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(0);
        });
    }
    
    // ═══════ ONLINE/OFFLINE HANDLERS ═══════
    
    function onOnline() {
        updateConnectionStatus();
        showOfflineToast('✅ Back online! Syncing data...');
        processSyncQueue();
    }
    
    function onOffline() {
        updateConnectionStatus();
        showOfflineToast('📡 You\'re offline. Data will sync when reconnected.');
    }
    
    function updateConnectionStatus() {
        const indicator = document.getElementById('connectionStatus');
        if (indicator) {
            if (navigator.onLine) {
                indicator.textContent = '🟢 Online';
                indicator.style.color = '#22C55E';
            } else {
                indicator.textContent = '🔴 Offline';
                indicator.style.color = '#EF4444';
            }
        }
    }
    
    // ═══════ UI HELPERS ═══════
    
    function showOfflineToast(message) {
        // Create toast if it doesn't exist
        let toast = document.getElementById('wangari-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'wangari-toast';
            toast.style.cssText = `
                position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
                background: #1a1a2e; color: #fff; padding: 12px 24px;
                border-radius: 12px; font-size: 0.9rem; z-index: 10000;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3); transition: all 0.3s;
                opacity: 0; max-width: 90%; text-align: center;
            `;
            document.body.appendChild(toast);
        }
        
        toast.textContent = message;
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(20px)';
        }, 3000);
    }
    
    // ═══════ HELPERS ═══════
    
    function getUserId() {
        // Try to get from meta tag or session
        const meta = document.querySelector('meta[name="user-id"]');
        if (meta) return parseInt(meta.content);
        
        // Try from global JS variable
        if (window.WANGARI_USER_ID) return window.WANGARI_USER_ID;
        
        return 0;
    }
    
    function getUserPhone() {
        const meta = document.querySelector('meta[name="user-phone"]');
        if (meta) return meta.content;
        if (window.WANGARI_USER_PHONE) return window.WANGARI_USER_PHONE;
        return '';
    }
    
    function buildProductionMessage(data) {
        let parts = [];
        if (data.eggs) parts.push(`eggs ${data.eggs}`);
        if (data.mortality) parts.push(`mortality ${data.mortality}`);
        if (data.milk) parts.push(`milk ${data.milk}`);
        return parts.join(', ') || 'summary';
    }
    
    // ═══════ PUBLIC API ═══════
    
    return {
        init,
        logProduction,
        logExpense,
        logSale,
        getPendingCount,
        processSyncQueue,
        isOnline: () => navigator.onLine
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => WangariOffline.init());
} else {
    WangariOffline.init();
}
