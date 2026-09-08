const CACHE = 'milkflow-public-v2';
const PUBLIC_FILES = ['/offline.html', '/offline.css', '/theme.js', '/theme.css', '/manifest.webmanifest', '/icons/milkflow-192.png', '/icons/milkflow-512.png'];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(PUBLIC_FILES)));
});
self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        for (const name of await caches.keys()) {
            if (name.startsWith('milkflow-public-') && name !== CACHE) await caches.delete(name);
        }
        await self.clients.claim();
    })());
});
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    // Never store authenticated HTML, API responses, reset tokens or CSRF tokens.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    } else if (PUBLIC_FILES.includes(url.pathname) && !url.search) {
        event.respondWith(caches.match(request).then(cached => cached || fetch(request)));
    }
});
