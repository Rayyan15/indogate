self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};

    event.waitUntil(self.registration.showNotification(payload.title || 'INDOGATE', {
        body: payload.body,
        icon: payload.icon,
        tag: payload.tag,
        data: payload.data,
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    // Only ever open a page on our own origin.
    const target = new URL(event.notification.data?.url || '/', self.location.origin);
    const url = target.origin === self.location.origin ? target.href : self.location.origin;

    event.waitUntil(self.clients.openWindow(url));
});
