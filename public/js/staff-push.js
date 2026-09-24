/* Alpine component for the "enable notifications on this device" button. */
function staffPush({ publicKey, storeUrl, destroyUrl, csrf }) {
    const request = (method, url, body) => fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(body),
    });

    const keyToBytes = (base64Url) => {
        const base64 = (base64Url + '='.repeat((4 - (base64Url.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');

        return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
    };

    return {
        supported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
        subscribed: false,
        denied: false,
        busy: false,

        async init() {
            if (!this.supported) return;
            this.denied = Notification.permission === 'denied';
            const registration = await navigator.serviceWorker.register('/sw.js');
            this.subscribed = !!(await registration.pushManager.getSubscription());
        },

        async enable() {
            this.busy = true;
            try {
                // Permission is asked only here, after the staff member pressed the button.
                if ((await Notification.requestPermission()) !== 'granted') {
                    this.denied = true;
                    return;
                }
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: keyToBytes(publicKey),
                });
                const json = subscription.toJSON();
                const response = await request('POST', storeUrl, {
                    endpoint: json.endpoint,
                    keys: json.keys,
                    contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                });
                if (!response.ok) {
                    await subscription.unsubscribe();
                    return;
                }
                this.subscribed = true;
            } finally {
                this.busy = false;
            }
        },

        async disable() {
            this.busy = true;
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();
                if (subscription) {
                    await request('DELETE', destroyUrl, { endpoint: subscription.endpoint });
                    await subscription.unsubscribe();
                }
                this.subscribed = false;
            } finally {
                this.busy = false;
            }
        },
    };
}
