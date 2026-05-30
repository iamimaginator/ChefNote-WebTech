const CACHE_NAME = 'chefnote-v1';
const ASSETS_TO_CACHE = [
    './style.css',
    './script.js',
    './logo.png',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'
];

// Install Event: Cache the static files
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

// Fetch Event: Serve from cache if offline
self.addEventListener('fetch', (event) => {
    // Only intercept basic GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});