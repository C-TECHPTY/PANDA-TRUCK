(function () {
    const root = document.querySelector('[data-live-chat]');
    if (!root) return;

    const apiBase = root.dataset.apiBase || 'api/chat';
    const els = {
        open: document.querySelector('[data-chat-open]'),
        overlay: root.querySelector('[data-chat-overlay]'),
        panel: root.querySelector('[data-chat-panel]'),
        close: root.querySelector('[data-chat-close]'),
        rules: root.querySelector('[data-chat-rules]'),
        pinned: root.querySelector('[data-chat-pinned]'),
        live: root.querySelector('[data-chat-live]'),
        liveTitle: root.querySelector('[data-chat-live-title]'),
        liveSubtitle: root.querySelector('[data-chat-live-subtitle]'),
        listeners: root.querySelector('[data-chat-listeners]'),
        poll: root.querySelector('[data-chat-poll]'),
        name: root.querySelector('[data-chat-name]'),
        saveName: root.querySelector('[data-chat-save-name]'),
        messages: root.querySelector('[data-chat-messages]'),
        form: root.querySelector('[data-chat-form]'),
        input: root.querySelector('[data-chat-input]'),
        emojiButtons: root.querySelectorAll('[data-chat-emoji]'),
        reactionButtons: root.querySelectorAll('[data-chat-reaction]')
    };

    const state = {
        clientId: localStorage.getItem('pandaChatClientId') || createClientId(),
        nickname: localStorage.getItem('pandaChatNickname') || '',
        deviceHash: createDeviceHash(),
        nicknameLocked: false,
        lastId: 0,
        lastReactionId: 0,
        loading: false,
        disabled: false,
        lastOpenAt: 0
    };

    localStorage.setItem('pandaChatClientId', state.clientId);
    els.name.value = state.nickname;
    configureEmojiPalette();
    configureReactionButtons();
    forceClosedOnLoad();
    initReactionCursor();
    loadPoll();

    function createClientId() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID().replace(/-/g, '');
        }
        return 'chat_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 18);
    }

    function createDeviceHash() {
        const parts = [
            navigator.userAgent || '',
            navigator.language || '',
            navigator.platform || '',
            screen.width || '',
            screen.height || '',
            screen.colorDepth || '',
            new Date().getTimezoneOffset(),
            navigator.hardwareConcurrency || '',
            navigator.maxTouchPoints || ''
        ].join('|');

        let hash = 2166136261;
        for (let i = 0; i < parts.length; i++) {
            hash ^= parts.charCodeAt(i);
            hash += (hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24);
        }

        return ('00000000' + (hash >>> 0).toString(16)).slice(-8) + simpleHash(parts.split('').reverse().join(''));
    }

    function simpleHash(value) {
        let hash = 0;
        for (let i = 0; i < value.length; i++) {
            hash = ((hash << 5) - hash + value.charCodeAt(i)) | 0;
        }
        return ('00000000' + (hash >>> 0).toString(16)).slice(-8);
    }

    function nickname() {
        return els.name.value.trim().slice(0, 40);
    }

    function request(path, options) {
        return fetch(apiBase + '/' + path, options).then((response) => response.json());
    }

    function openChat() {
        const now = Date.now();
        if (now - state.lastOpenAt < 350) return;
        state.lastOpenAt = now;
        root.classList.add('is-open');
        els.overlay.classList.add('is-open');
        els.panel.classList.add('is-open');
        forcePanelVisible();
        document.body.style.overflow = 'hidden';
        refreshState();
        if (window.matchMedia('(min-width: 761px)').matches) {
            setTimeout(() => (state.nickname ? els.input : els.name).focus(), 180);
        }
    }

    function closeChat() {
        root.classList.remove('is-open');
        els.overlay.classList.remove('is-open');
        els.panel.classList.remove('is-open');
        clearForcedPanelStyles();
        document.body.style.overflow = '';
    }

    function forcePanelVisible() {
        if (!els.panel) return;
        els.panel.style.visibility = 'visible';
        els.panel.style.opacity = '1';
        els.panel.style.pointerEvents = 'auto';
        els.panel.style.display = 'flex';
        els.panel.style.transform = 'translate3d(0, 0, 0)';

        if (window.matchMedia('(max-width: 760px)').matches) {
            els.panel.style.left = '0';
            els.panel.style.right = '0';
            els.panel.style.bottom = '0';
            els.panel.style.top = 'auto';
            els.panel.style.width = '100vw';
            els.panel.style.height = '68vh';
            els.panel.style.maxHeight = '68vh';
        }
    }

    function clearForcedPanelStyles() {
        if (!els.panel) return;
        [
            'visibility', 'opacity', 'pointerEvents', 'display', 'transform',
            'left', 'right', 'bottom', 'top', 'width', 'height', 'maxHeight'
        ].forEach((prop) => {
            els.panel.style[prop] = '';
        });
    }

    function forceClosedOnLoad() {
        root.classList.remove('is-open');
        els.overlay?.classList.remove('is-open');
        els.panel?.classList.remove('is-open');
        document.body.style.overflow = '';

        window.addEventListener('pageshow', () => {
            root.classList.remove('is-open');
            els.overlay?.classList.remove('is-open');
            els.panel?.classList.remove('is-open');
            document.body.style.overflow = '';
        });
    }

    function refreshState() {
        request('state.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ client_id: state.clientId, device_hash: state.deviceHash, nickname: nickname() })
        }).then((data) => {
            if (!data.success) return;
            state.nickname = data.user.nickname || '';
            state.nicknameLocked = !!data.user.nickname_locked;
            if (state.nicknameLocked) {
                els.name.value = state.nickname;
                els.name.disabled = true;
                els.saveName.disabled = true;
                els.saveName.classList.add('is-locked');
                localStorage.setItem('pandaChatNickname', state.nickname);
            } else {
                els.name.disabled = false;
                els.saveName.disabled = false;
                els.saveName.classList.remove('is-locked');
            }
            els.rules.textContent = data.settings.rules || '';
            updatePinnedAnnouncement(data.settings.pinned_announcement || '');
            updateLiveStatus(data.settings || {}, data.active_listeners || 0);
            state.disabled = data.user.is_banned || !data.settings.enabled;
            if (state.disabled) {
                els.input.disabled = true;
                els.input.placeholder = data.user.banned_reason || 'El chat esta desactivado.';
            } else {
                els.input.disabled = false;
                els.input.placeholder = 'Escribe tu saludo o complacencia...';
            }
            loadMessages();
        }).catch(() => {});
    }

    function saveName() {
        if (state.nicknameLocked) return;
        const nextNickname = nickname();
        if (!nextNickname) {
            showInlineNotice('Escribe tu nombre para participar. Luego quedara fijo en este dispositivo.');
            els.name.focus();
            return;
        }
        state.nickname = nextNickname;
        localStorage.setItem('pandaChatNickname', state.nickname);
        refreshState();
    }

    function loadMessages() {
        if (state.loading) return;
        state.loading = true;
        const params = new URLSearchParams({
            client_id: state.clientId,
            device_hash: state.deviceHash,
            nickname: nickname(),
            after_id: String(state.lastId)
        });
        request('messages.php?' + params.toString())
            .then((data) => {
                if (!data.success) return;
                if (data.messages.length && els.messages.querySelector('.live-chat-empty')) {
                    els.messages.innerHTML = '';
                }
                data.messages.forEach(addMessage);
            })
            .finally(() => {
                state.loading = false;
            });
    }

    function addMessage(message) {
        state.lastId = Math.max(state.lastId, Number(message.id));
        const item = document.createElement('div');
        const isMe = message.sender_client_id === state.clientId;
        item.className = 'live-chat-message' + (isMe ? ' is-me' : '') + (message.is_private ? ' is-private' : '') + (message.is_featured ? ' is-featured' : '');
        const badge = message.badge === 'crown' ? '<i class="fas fa-crown live-chat-badge"></i>' : message.badge === 'star' ? '<i class="fas fa-star live-chat-badge"></i>' : '';
        const roleLabel = message.badge === 'crown'
            ? '<span class="live-chat-role-label">Super Admin</span>'
            : message.badge === 'star'
                ? `<span class="live-chat-role-label">${message.role === 'chat_moderator' ? 'Moderador' : 'Admin'}</span>`
                : '';
        const featuredLabel = message.is_featured ? '<span class="live-chat-featured-label">Destacado</span>' : '';
        const privateLabel = message.is_private ? '<span>Privado</span>' : '';
        item.innerHTML = `
            <div class="live-chat-bubble">
                <div class="live-chat-meta">${badge}<span>${escapeHtml(message.nickname)}</span>${roleLabel}${featuredLabel}${privateLabel}</div>
                <div class="live-chat-text">${escapeHtml(message.message)}</div>
            </div>
        `;
        els.messages.appendChild(item);
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    function sendMessage(event) {
        event.preventDefault();
        if (state.disabled) return;
        const message = els.input.value.trim();
        if (!message) return;
        if (!state.nicknameLocked && !nickname()) {
            showInlineNotice('Primero escribe tu nombre. Solo podras usar un nick por este celular o PC.');
            els.name.focus();
            return;
        }
        saveName();
        els.input.value = '';
        request('send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                client_id: state.clientId,
                device_hash: state.deviceHash,
                nickname: nickname(),
                message
            })
        }).then((data) => {
            if (data.success) {
                loadMessages();
            } else {
                showInlineNotice(data.error || 'No se pudo enviar.');
            }
        }).catch(() => showInlineNotice('Error de conexion.'));
    }

    function showInlineNotice(text) {
        const item = document.createElement('div');
        item.className = 'live-chat-message';
        item.innerHTML = `<div class="live-chat-bubble"><div class="live-chat-text">${escapeHtml(text)}</div></div>`;
        els.messages.appendChild(item);
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    function updatePinnedAnnouncement(text) {
        if (!els.pinned) return;
        const clean = String(text || '').trim();
        els.pinned.textContent = clean;
        els.pinned.classList.toggle('hidden', clean === '');
    }

    function updateLiveStatus(settings, activeListeners) {
        if (els.liveTitle) {
            els.liveTitle.textContent = settings.live_title || 'Ahora en vivo';
        }
        if (els.liveSubtitle) {
            const parts = [settings.live_host || '', settings.live_program || ''].filter(Boolean);
            els.liveSubtitle.textContent = parts.length ? parts.join(' - ') : 'Panda Truck Reloaded';
        }
        if (els.listeners) {
            const total = Number(activeListeners || 0);
            els.listeners.textContent = total === 1 ? '1 oyente' : `${total} oyentes`;
        }
    }

    function insertEmoji(emoji) {
        els.input.value = (els.input.value + ' ' + emoji).trimStart();
        els.input.focus();
    }

    function sendReaction(reaction, button) {
        request('react.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                client_id: state.clientId,
                device_hash: state.deviceHash,
                nickname: nickname(),
                reaction
            })
        }).catch(() => {});
        if (reaction === '❤️') {
            heartBurst(button);
        } else {
            floatReaction(reaction, button);
        }
    }

    function initReactionCursor() {
        request('reactions.php?latest=1')
            .then((data) => {
                if (data.success) {
                    state.lastReactionId = Number(data.last_id || 0);
                }
            })
            .catch(() => {});
    }

    function loadReactions() {
        const params = new URLSearchParams({ after_id: String(state.lastReactionId) });
        request('reactions.php?' + params.toString())
            .then((data) => {
                if (!data.success) return;
                (data.reactions || []).forEach((reaction) => {
                    state.lastReactionId = Math.max(state.lastReactionId, Number(reaction.id || 0));
                    if (reaction.sender_client_id === state.clientId) return;
                    animateSharedReaction(reaction.reaction);
                });
            })
            .catch(() => {});
    }

    function animateSharedReaction(reaction) {
        const point = {
            getBoundingClientRect() {
                return {
                    left: Math.max(24, window.innerWidth - 74),
                    top: Math.max(120, window.innerHeight - 180),
                    width: 44,
                    height: 44
                };
            }
        };

        if (isHeartReaction(reaction)) {
            heartBurst(point);
        } else {
            floatReaction(reaction, point);
        }
    }

    function loadPoll() {
        if (!els.poll) return;
        const params = new URLSearchParams({
            client_id: state.clientId,
            device_hash: state.deviceHash
        });
        request('poll.php?' + params.toString())
            .then((data) => {
                if (!data.success) return;
                renderPoll(data.poll);
            })
            .catch(() => {});
    }

    function renderPoll(poll) {
        if (!els.poll) return;
        if (!poll) {
            els.poll.classList.add('hidden');
            els.poll.innerHTML = '';
            return;
        }

        const total = Number(poll.total_votes || 0);
        els.poll.classList.remove('hidden');
        els.poll.innerHTML = `
            <div class="live-chat-poll-head">
                <span>Encuesta</span>
                <strong>${escapeHtml(poll.question)}</strong>
            </div>
            <div class="live-chat-poll-options">
                ${(poll.options || []).map((option) => {
                    const votes = Number(option.votes || 0);
                    const percent = total > 0 ? Math.round((votes / total) * 100) : 0;
                    const voted = Number(poll.voted_option_id || 0) === Number(option.id);
                    const voteLabel = `${votes} voto${votes === 1 ? '' : 's'}`;
                    return `
                        <button type="button" data-chat-poll-option="${option.id}" class="${voted ? 'is-voted' : ''}" ${poll.voted_option_id ? 'disabled' : ''}>
                            <span>${escapeHtml(option.option_text)}</span>
                            <em>${percent}% <small>${voteLabel}</small></em>
                            <i style="width:${percent}%"></i>
                        </button>
                    `;
                }).join('')}
            </div>
            <div class="live-chat-poll-total">${total} voto${total === 1 ? '' : 's'}</div>
        `;
    }

    function votePoll(optionId) {
        request('poll.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                client_id: state.clientId,
                device_hash: state.deviceHash,
                nickname: nickname(),
                option_id: optionId
            })
        }).then((data) => {
            if (data.success) {
                renderPoll(data.poll);
            }
        }).catch(() => {});
    }

    function isHeartReaction(reaction) {
        const value = String(reaction || '');
        return value.includes('â¤') || value.includes('❤');
    }

    function floatReaction(reaction, button) {
        const rect = button.getBoundingClientRect();
        const floater = document.createElement('div');
        floater.className = 'live-chat-float';
        floater.textContent = reaction;
        floater.style.left = rect.left + rect.width / 2 + 'px';
        floater.style.top = rect.top + 'px';
        document.body.appendChild(floater);
        setTimeout(() => floater.remove(), 1300);
    }

    function heartBurst(button) {
        const rect = button.getBoundingClientRect();
        const startX = rect.left + rect.width / 2;
        const startY = rect.top + rect.height / 2;
        const hearts = ['❤️', '💖', '💗', '💓', '💘'];

        for (let i = 0; i < 12; i++) {
            const heart = document.createElement('div');
            heart.className = 'live-chat-heart';
            heart.textContent = hearts[i % hearts.length];
            heart.style.left = startX + (Math.random() * 28 - 14) + 'px';
            heart.style.top = startY + (Math.random() * 16 - 8) + 'px';
            heart.style.setProperty('--drift', (Math.random() * 120 - 60).toFixed(0) + 'px');
            heart.style.setProperty('--rise', (150 + Math.random() * 170).toFixed(0) + 'px');
            heart.style.animationDelay = (i * 0.035).toFixed(2) + 's';
            document.body.appendChild(heart);
            setTimeout(() => heart.remove(), 1700);
        }
    }

    function configureEmojiPalette() {
        const palette = root.querySelector('.live-chat-quick-emojis');
        if (!palette) return;
        const emojis = [
            '👍', '😭', '🙌', '😕', '😍', '🥵', '😁', '🤭', '😢', '👏',
            '🥶', '🤔', '😜', '🧠', '👉', '⚠️', '😘', '🥺', '🤤', '🤢',
            '🐸', '🫡', '🤫', '👀', '💪', '🤣', '✌️', '😱', '🤐', '🌲',
            '🎄', '😀', '😃', '😄', '😆', '😇', '🙂', '😉', '😊', '😎',
            '🤓', '🥳', '💯', '🚀', '⭐', '👑', '💃', '🕺', '🎧', '📻',
            '🔊', '🔥', '❤️', '🎶'
        ];
        palette.innerHTML = emojis.map((emoji) => (
            `<button type="button" data-chat-emoji="${emoji}" aria-label="Agregar emoji ${emoji}">${emoji}</button>`
        )).join('');
        els.emojiButtons = root.querySelectorAll('[data-chat-emoji]');
    }

    function configureReactionButtons() {
        const reactionContainers = root.querySelectorAll('.live-chat-reactions, [data-chat-inline-reactions]');
        if (reactionContainers.length) {
            const reactionsList = [
                [String.fromCodePoint(0x2764, 0xFE0F), 'Enviar corazon', 'live-chat-like'],
                [String.fromCodePoint(0x1F525), 'Enviar fuego', ''],
                [String.fromCodePoint(0x1F44F), 'Enviar aplauso', ''],
                [String.fromCodePoint(0x1F3B6), 'Enviar musica', '']
            ];
            const buttons = reactionsList.map(([emoji, label, className]) => (
                `<button type="button" class="${className}" data-chat-reaction="${emoji}" aria-label="${label}" title="${label}">${emoji}</button>`
            )).join('');
            reactionContainers.forEach((container) => {
                container.innerHTML = buttons;
            });
            els.reactionButtons = root.querySelectorAll('[data-chat-reaction]');
            return;
        }
        const reactions = root.querySelector('.live-chat-reactions');
        if (!reactions) return;
        reactions.innerHTML = [
            ['❤️', 'Enviar corazon', 'live-chat-like'],
            ['🔥', 'Enviar fuego', ''],
            ['👏', 'Enviar aplauso', ''],
            ['🎶', 'Enviar musica', '']
        ].map(([emoji, label, className]) => (
            `<button type="button" class="${className}" data-chat-reaction="${emoji}" aria-label="${label}">${emoji}</button>`
        )).join('');
        els.reactionButtons = root.querySelectorAll('[data-chat-reaction]');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function closestFromEvent(event, selector) {
        const target = event.target;
        if (!target) return null;
        if (target.closest) {
            return target.closest(selector);
        }
        if (target.parentElement && target.parentElement.closest) {
            return target.parentElement.closest(selector);
        }
        return null;
    }

    function handleOpenEvent(event) {
        const opener = closestFromEvent(event, '[data-chat-open]');
        if (!opener) return;
        const href = opener.getAttribute('href');
        if (href && window.matchMedia('(max-width: 760px)').matches) {
            return;
        }
        if (event.cancelable) {
            event.preventDefault();
        }
        event.stopPropagation();
        openChat();
    }

    window.PandaLiveChatOpen = function (event) {
        if (event && event.cancelable) {
            event.preventDefault();
        }
        openChat();
        return false;
    };

    ['click', 'touchend', 'pointerup'].forEach((eventName) => {
        document.addEventListener(eventName, handleOpenEvent, { capture: true, passive: false });
        els.open?.addEventListener(eventName, handleOpenEvent, { passive: false });
    });
    root.addEventListener('click', (event) => {
        const pollOption = closestFromEvent(event, '[data-chat-poll-option]');
        if (pollOption) {
            event.preventDefault();
            votePoll(pollOption.dataset.chatPollOption);
            return;
        }
        if (closestFromEvent(event, '[data-chat-close]')) {
            if (event.cancelable) {
                event.preventDefault();
            }
            event.stopPropagation();
            closeChat();
        }
    });
    root.addEventListener('touchend', (event) => {
        if (closestFromEvent(event, '[data-chat-close]')) {
            if (event.cancelable) {
                event.preventDefault();
            }
            event.stopPropagation();
            closeChat();
        }
    }, { passive: false });
    els.overlay?.addEventListener('click', closeChat);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('is-open')) {
            closeChat();
        }
    });
    els.saveName?.addEventListener('click', saveName);
    els.form?.addEventListener('submit', sendMessage);
    els.emojiButtons.forEach((button) => button.addEventListener('click', () => insertEmoji(button.dataset.chatEmoji)));
    els.reactionButtons.forEach((button) => button.addEventListener('click', () => sendReaction(button.dataset.chatReaction, button)));

    refreshState();
    setInterval(loadMessages, 2500);
    setInterval(loadReactions, 1200);
    setInterval(loadPoll, 5000);
    setInterval(refreshState, 15000);
})();
