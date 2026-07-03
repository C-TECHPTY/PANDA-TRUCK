const PANDA_CACHE = 'panda-truck-pwa-v3';
const CORE_ASSETS = [
  '/assets/img/logo.png',
  '/assets/img/android-chrome-192x192.png',
  '/assets/img/android-chrome-512x512.png',
  '/assets/img/apple-touch-icon.png',
  '/assets/img/default-cover.jpg',
  '/assets/css/style.css',
  '/assets/js/pwa.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(PANDA_CACHE)
      .then(cache => cache.addAll(CORE_ASSETS))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== PANDA_CACHE).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  const isApiRequest = url.pathname.startsWith('/api/');
  const isDynamicPage = url.pathname === '/' || url.pathname.endsWith('.php');
  const isStaticAsset = /\.(?:css|js|png|jpg|jpeg|gif|webp|svg|ico|woff2?)$/i.test(url.pathname);

  if (isApiRequest) return;

  if (request.mode === 'navigate' || isDynamicPage) {
    event.respondWith(
      fetch(request).catch(() => caches.match('/index.php'))
    );
    return;
  }

  if (!isStaticAsset) return;

  event.respondWith(
    fetch(request)
      .then(response => {
        if (!response || response.status !== 200) return response;
        const copy = response.clone();
        caches.open(PANDA_CACHE).then(cache => cache.put(request, copy)).catch(() => {});
        return response;
      })
      .catch(() => caches.match(request))
  );
});

self.addEventListener('push', event => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { title: 'Panda Truck Reloaded', body: event.data ? event.data.text() : '' };
  }

  const title = data.title || 'Panda Truck Reloaded';
  const options = {
    body: data.body || 'Nuevo aviso en la plataforma',
    icon: data.icon || '/assets/img/android-chrome-192x192.png',
    badge: data.badge || '/assets/img/favicon-32x32.png',
    image: data.image || undefined,
    data: {
      url: data.url || '/index.php#radio'
    },
    tag: data.tag || 'panda-truck-live',
    renotify: true,
    silent: false,
    timestamp: Date.now(),
    vibrate: data.vibrate || [300, 120, 300, 120, 300],
    requireInteraction: data.requireInteraction !== false,
    actions: data.actions || [
      { action: 'open-radio', title: 'Abrir radio' }
    ]
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const targetUrl = new URL(event.notification.data?.url || '/index.php#radio', self.location.origin).href;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if ('focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }
      return clients.openWindow(targetUrl);
    })
  );
});
