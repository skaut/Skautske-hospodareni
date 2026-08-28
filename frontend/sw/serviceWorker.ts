/// <reference lib="webworker" />

// Marks the file as a module so the worker-scoped `self` can be declared here.
export {};

declare const self: ServiceWorkerGlobalScope;

const CACHE_NAME = 'hospodareni-shell-v1';
const OFFLINE_PAGE = '/offline.html';

self.addEventListener('install', (event: ExtendableEvent) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.add(OFFLINE_PAGE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event: ExtendableEvent) => {
    event.waitUntil(
        caches.keys()
            .then((names) => Promise.all(
                names.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name)),
            ))
            .then(() => self.clients.claim()),
    );
});

// Every page of the application is session bound and reflects live SkautIS data,
// so nothing but the offline fallback is ever served from the cache.
self.addEventListener('fetch', (event: FetchEvent) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(async () => await caches.match(OFFLINE_PAGE) ?? Response.error()),
    );
});
