(function () {
    const state = {
        registration: null,
        config: null,
        subscribePromise: null
    };

    function isSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    }

    function base64UrlToUint8Array(value) {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        const output = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; i++) output[i] = rawData.charCodeAt(i);
        return output;
    }

    function sameApplicationServerKey(subscription, publicKey) {
        if (!subscription?.options?.applicationServerKey) return true;
        const current = new Uint8Array(subscription.options.applicationServerKey);
        const expected = base64UrlToUint8Array(publicKey);
        if (current.length !== expected.length) return false;
        return current.every((value, index) => value === expected[index]);
    }

    async function loadConfig() {
        if (state.config) return state.config;
        const res = await fetch('/api/push_config.php', { credentials: 'same-origin' });
        state.config = await res.json();
        return state.config;
    }

    async function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return null;
        if (state.registration) return state.registration;
        await navigator.serviceWorker.register('/service-worker.js');
        state.registration = await navigator.serviceWorker.ready;
        return state.registration;
    }

    async function subscribe() {
        if (state.subscribePromise) return state.subscribePromise;
        state.subscribePromise = subscribeNow().finally(() => {
            state.subscribePromise = null;
        });
        return state.subscribePromise;
    }

    async function subscribeNow() {
        if (!isSupported()) {
            throw new Error('Este navegador no soporta notificaciones push web.');
        }

        const config = await loadConfig();
        if (!config.enabled || !config.publicKey) {
            throw new Error(config.error || 'Las notificaciones push no estan configuradas todavia.');
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Permiso de notificaciones no concedido.');
        }

        const registration = await registerServiceWorker();
        let subscription = await registration.pushManager.getSubscription();
        if (subscription && !sameApplicationServerKey(subscription, config.publicKey)) {
            await subscription.unsubscribe();
            subscription = null;
        }

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64UrlToUint8Array(config.publicKey)
            });
        }

        const save = await fetch('/api/push_subscribe.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription)
        });
        const result = await save.json();
        if (!result.success) {
            throw new Error(result.error || 'No se pudo guardar este dispositivo.');
        }

        return result;
    }

    function friendlyPushError(error) {
        const message = error?.message || '';
        if (/push service|registration failed|push service error/i.test(message)) {
            console.log('Error tecnico push:', error);
            return 'No pudimos activar los avisos en este momento. Intentalo otra vez mas tarde.';
        }
        return message || 'No se pudo activar.';
    }

    async function activateButtonSubscription(button, options = {}) {
        button.disabled = true;
        button.textContent = 'Activando...';
        try {
            await subscribe();
            button.textContent = 'Avisos activos';
            if (!options.keepVisible) setTimeout(() => button.remove(), 1600);
        } catch (error) {
            button.disabled = false;
            button.textContent = Notification.permission === 'granted' ? 'Reactivar avisos' : 'Activar avisos';
            alert(friendlyPushError(error));
        }
    }

    async function syncGrantedPermission() {
        if (!isSupported() || Notification.permission !== 'granted') return;
        try {
            await subscribe();
        } catch (error) {
            console.log('No se pudo sincronizar push:', error);
        }
    }

    function setupPushButton(button, options = {}) {
        if (!button || button.dataset.pandaPushReady === '1') return;
        button.dataset.pandaPushReady = '1';

        if (!isSupported()) {
            if (options.keepVisible) {
                button.classList.remove('hidden');
                button.disabled = true;
                button.textContent = 'Avisos no disponibles';
            }
            return;
        }

        if (Notification.permission === 'granted') {
            button.classList.remove('hidden');
            activateButtonSubscription(button, options);
            return;
        }

        if (Notification.permission === 'denied') {
            button.classList.remove('hidden');
            button.disabled = true;
            button.textContent = 'Avisos bloqueados';
            return;
        }

        button.classList.remove('hidden');
        button.addEventListener('click', async () => {
            activateButtonSubscription(button, options);
        });
    }

    function installPromptButton() {
        const existingButtons = Array.from(document.querySelectorAll('[data-panda-push-button]'));
        existingButtons.forEach(button => setupPushButton(button, { keepVisible: true }));

        if (!isSupported() || Notification.permission !== 'default') return;
        if (existingButtons.length > 0) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.pandaPushButton = '1';
        button.textContent = 'Activar avisos';
        button.style.cssText = 'position:fixed;right:14px;bottom:14px;z-index:60;background:#e1261d;color:#fff;border:0;border-radius:999px;padding:10px 14px;font:700 13px system-ui,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.35);';
        document.body.appendChild(button);
        setupPushButton(button);
    }

    window.PandaPWA = {
        isSupported,
        registerServiceWorker,
        subscribe,
        syncGrantedPermission,
        loadConfig
    };

    registerServiceWorker().catch(() => {});
    syncGrantedPermission();

    if (!document.body?.dataset.noPushPrompt) {
        setTimeout(installPromptButton, 2200);
    }
})();
