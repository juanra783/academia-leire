const CACHE_NAME='academia-leire-v6';
const CORE=['./','./dashboard.php?v=6','./login.php?v=6','./repaso.php?v=6','./crazy_exam.php?v=6','./trainer.php','./shop.php','./battle.php','./scan.php','./assets/css/style.css?v=6','./assets/js/crazy-exam-v6.js?v=6','./manifest.webmanifest?v=6','./assets/icons/icon-leire-192.png?v=6','./assets/icons/icon-leire-512.png?v=6','./assets/icons/apple-touch-icon.png?v=6','./favicon.ico?v=6'];
self.addEventListener('install',e=>{self.skipWaiting();e.waitUntil(caches.open(CACHE_NAME).then(c=>c.addAll(CORE)).catch(()=>{}))});
self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(x=>x!==CACHE_NAME).map(x=>caches.delete(x)))).then(()=>self.clients.claim()))});
self.addEventListener('fetch',e=>{e.respondWith(fetch(e.request).catch(()=>caches.match(e.request)))});
