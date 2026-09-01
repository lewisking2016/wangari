/**
 * Wangari Service Worker — Offline-First Architecture
 * 
 * Enables farmers to enter data without internet.
 * Data is stored in IndexedDB and synced when connection returns.
 * 
 * Phase 1: Basic offline caching + IndexedDB sync queue
 * Phase 2: Full offline dashboard (cached reports)
 */

const CACHE_NAME = 'wangari-v1';
const STATIC_ASSETS = [
    '/',
    '/Frontend/index.php',
    '/Frontend/pages/login.php',
    '/Frontend/pages/dashboard.php',
    '/Frontend/assets/css/xai-public.css',
    '/Frontend/assets/css/mobile-fix.css',
    '/Frontend/images/wangari-logo.png',
];

// ═══════ INSTALL ═══════
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ═══════ ACTIVATE ═══════
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// ═══════ FETCH (Cache-First for static, Network-First for API) ═══════
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // API requests: Network-First (fallback to cached)
    if (url.pathname.includes('/Backend/api/')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache successful API responses for offline use
                    if (response.ok) {
                        const cloned = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, cloned));
                    }
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }
    
    // Static assets: Cache-First
    event.respondWith(
        caches.match(request)
            .then(cached => cached || fetch(request)
                .then(response => {
                    const cloned = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, cloned));
                    return response;
                })
            )
    );
});

// ═══════ BACKGROUND SYNC ═══════
self.addEventListener('sync', (event) => {
    if (event.tag === 'wangari-sync') {
        event.waitUntil(syncPendingData());
    }
});

// ═══════ PUSH NOTIFICATIONS ═══════
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Wangari';
    const options = {
        body: data.body || 'You have a new notification',
        icon: '/Frontend/images/wangari-logo.png',
        badge: '/Frontend/images/wangari-logo.png',
        data: data.url || '/',
        actions: [
            { action: 'open', title: 'Open' },
            { action: 'dismiss', title: 'Dismiss' }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.action === 'open' || !event.action) {
        event.waitUntil(
            clients.openWindow(event.notification.data)
        );
    }
});

// ═══════ OFFLINE DATA SYNC ═══════
async function syncPendingData() {
    try {
        // Open IndexedDB
        const db = await openDB();
        const tx = db.transaction('syncQueue', 'readwrite');
        const store = tx.objectStore('syncQueue');
        const request = store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = async () => {
                const pendingItems = request.result;
                
                for (const item of pendingItems) {
                    try {
                        const response = await fetch(item.url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(item.data)
                        });
                        
                        if (response.ok) {
                            // Remove from queue on success
                            store.delete(item.id);
                        }
                    } catch (e) {
                        // Will retry on next sync
                        console.log('Sync failed for item:', item.id);
                    }
                }
                
                resolve();
            };
            request.onerror = reject;
        });
    } catch (e) {
        console.error('Sync error:', e);
    }
}

// ═══════ IndexedDB HELPER ═══════
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('WangariOffline', 1);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Sync queue for pending data entries
            if (!db.objectStoreNames.contains('syncQueue')) {
                const store = db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
                store.createIndex('status', 'status', { unique: false });
            }
            
            // Cached production data (for offline viewing)
            if (!db.objectStoreNames.contains('productionCache')) {
                db.createObjectStore('productionCache', { keyPath: ['user_id', 'record_date'] });
            }
            
            // Cached inventory (for offline viewing)
            if (!db.objectStoreNames.contains('inventoryCache')) {
                db.createObjectStore('inventoryCache', { keyPath: 'id' });
            }
        };
        
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// ═══════ PUBLIC API (called from main thread) ═══════
// These are exposed via postMessage from the main page

self.addEventListener('message', (event) => {
    const { type, data } = event.data;
    
    switch (type) {
        case 'QUEUE_FOR_SYNC':
            queueForSync(data);
            break;
        case 'FORCE_SYNC':
            syncPendingData();
            break;
        case 'CACHE_PRODUCTION':
            cacheProduction(data);
            break;
    }
});

async function queueForSync(data) {
    const db = await openDB();
    const tx = db.transaction('syncQueue', 'readwrite');
    const store = tx.objectStore('syncQueue');
    
    store.add({
        url: data.url || '/Backend/api/whatsapp_bot.php',
        data: data.payload,
        timestamp: Date.now(),
        status: 'pending'
    });
    
    // Try to sync immediately
    if (navigator.onLine) {
        syncPendingData();
    } else {
        // Register for background sync when online
        self.registration.sync.register('wangari-sync');
    }
}

async function cacheProduction(data) {
    const db = await openDB();
    const tx = db.transaction('productionCache', 'readwrite');
    const store = tx.objectStore('productionCache');
    store.put(data);
}
