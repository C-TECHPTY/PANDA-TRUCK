(function () {
    const script = document.currentScript;
    const apiBase = script?.dataset.apiBase || 'api';
    const endpoint = `${apiBase.replace(/\/$/, '')}/toggle_like.php`;

    function formatNumber(value) {
        const number = Number.parseInt(value, 10) || 0;
        return number.toLocaleString();
    }

    function buttonsFor(mixId) {
        return document.querySelectorAll(`.mix-like-btn[data-mix-id="${mixId}"]`);
    }

    function updateButton(button, state) {
        const icon = button.querySelector('i');
        const count = button.querySelector('.mix-like-count');
        button.dataset.liked = state.liked ? '1' : '0';
        button.classList.toggle('text-red-500', state.liked);
        button.classList.toggle('text-neutral-400', !state.liked);
        button.setAttribute('aria-pressed', state.liked ? 'true' : 'false');
        button.title = state.liked ? 'Quitar me gusta' : 'Me gusta';

        if (icon) {
            icon.classList.toggle('fas', state.liked);
            icon.classList.toggle('far', !state.liked);
        }
        if (count) {
            count.textContent = formatNumber(state.likes);
        }
    }

    function updateAllForMix(mixId, state) {
        buttonsFor(mixId).forEach((button) => updateButton(button, state));
    }

    async function post(payload) {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'No se pudo actualizar el me gusta');
        }
        return data;
    }

    async function loadInitialState() {
        const buttons = Array.from(document.querySelectorAll('.mix-like-btn[data-mix-id]'));
        const ids = [...new Set(buttons.map((button) => parseInt(button.dataset.mixId, 10)).filter(Boolean))];
        if (!ids.length) return;

        try {
            const data = await post({ action: 'status', ids });
            Object.entries(data.mixes || {}).forEach(([mixId, state]) => updateAllForMix(mixId, state));
        } catch (error) {
            console.error('Error cargando likes:', error);
        }
    }

    async function toggleLike(button) {
        const mixId = parseInt(button.dataset.mixId, 10);
        if (!mixId || button.dataset.loading === '1') return;

        button.dataset.loading = '1';
        try {
            const data = await post({ action: 'toggle', mix_id: mixId });
            updateAllForMix(mixId, { likes: data.likes, liked: data.liked });
        } catch (error) {
            console.error('Error actualizando like:', error);
        } finally {
            button.dataset.loading = '0';
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.mix-like-btn[data-mix-id]');
        if (!button) return;

        event.preventDefault();
        event.stopPropagation();
        toggleLike(button);
    });

    document.addEventListener('DOMContentLoaded', loadInitialState);
    setInterval(loadInitialState, 30000);

    window.PandaMixLikes = {
        refresh: loadInitialState,
        updateAllForMix,
    };
})();
