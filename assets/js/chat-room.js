(function () {
    const root = document.querySelector('[data-chat-room]');
    if (!root) return;

    const apiBase = root.dataset.apiBase || 'api/chat';
    const els = {
        rules: root.querySelector('[data-room-rules]'),
        pinned: root.querySelector('[data-room-pinned]'),
        liveTitle: root.querySelector('[data-room-live-title]'),
        liveSubtitle: root.querySelector('[data-room-live-subtitle]'),
        listeners: root.querySelector('[data-room-listeners]'),
        poll: root.querySelector('[data-room-poll]'),
        name: root.querySelector('[data-room-name]'),
        saveName: root.querySelector('[data-room-save-name]'),
        messages: root.querySelector('[data-room-messages]'),
        form: root.querySelector('[data-room-form]'),
        input: root.querySelector('[data-room-input]'),
        emojis: root.querySelector('[data-room-emojis]'),
        reactions: root.querySelector('[data-room-reactions]')
    };

    const state = {
        clientId: localStorage.getItem('pandaChatClientId') || createClientId(),
        nickname: localStorage.getItem('pandaChatNickname') || '',
        deviceHash: createDeviceHash(),
        nicknameLocked: false,
        lastId: 0,
        lastReactionId: 0,
        loading: false,
        disabled: false
    };

    localStorage.setItem('pandaChatClientId', state.clientId);
    els.name.value = state.nickname;

    setupRadio();
    renderEmojiPalette();
    renderReactions();
    initReactionCursor();
    loadPoll();
    refreshState();
    setInterval(loadMessages, 2500);
    setInterval(loadReactions, 1200);
    setInterval(loadPoll, 5000);
    setInterval(refreshState, 15000);

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
        return simpleHash(parts) + simpleHash(parts.split('').reverse().join(''));
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
            els.input.disabled = state.disabled;
            els.input.placeholder = state.disabled ? (data.user.banned_reason || 'El chat esta desactivado.') : 'Escribe tu saludo o complacencia...';
            loadMessages();
        }).catch(() => showInlineNotice('No se pudo conectar con el chat.'));
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

    function renderEmojiPalette() {
        const emojis = [
            0x1F44D, 0x1F62D, 0x1F64C, 0x1F615, 0x1F60D, 0x1F975, 0x1F601, 0x1F92D,
            0x1F622, 0x1F44F, 0x1F976, 0x1F914, 0x1F61C, 0x1F9E0, 0x1F449, 0x26A0,
            0x1F618, 0x1F97A, 0x1F924, 0x1F922, 0x1F440, 0x1F4AA, 0x1F923, 0x1F631,
            0x1F60E, 0x1F973, 0x1F4AF, 0x1F680, 0x2B50, 0x1F451, 0x1F483, 0x1F57A,
            0x1F3A7, 0x1F4FB, 0x1F50A, 0x1F525, 0x2764, 0x1F3B6
        ].map((code) => String.fromCodePoint(code));
        els.emojis.innerHTML = emojis.map((emoji) => (
            `<button type="button" data-room-emoji="${emoji}" aria-label="Agregar emoji">${emoji}</button>`
        )).join('');
    }

    function renderReactions() {
        const reactions = ['❤️', '🔥', '👏', '🎶'];
        els.reactions.innerHTML = reactions.map((emoji) => (
            `<button type="button" data-room-reaction="${emoji}" aria-label="Reaccion">${emoji}</button>`
        )).join('');
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

        floatReaction(reaction, button);
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
                    left: Math.max(24, window.innerWidth - 78),
                    top: Math.max(120, window.innerHeight - 190),
                    width: 44,
                    height: 44
                };
            }
        };
        floatReaction(reaction, point);
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
                        <button type="button" data-room-poll-option="${option.id}" class="${voted ? 'is-voted' : ''}" ${poll.voted_option_id ? 'disabled' : ''}>
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

    function floatReaction(reaction, button) {
        const rect = button.getBoundingClientRect();
        for (let i = 0; i < (reaction === '❤️' ? 10 : 1); i++) {
            const floater = document.createElement('div');
            floater.className = reaction === '❤️' ? 'live-chat-heart' : 'live-chat-float';
            floater.textContent = reaction;
            floater.style.left = rect.left + rect.width / 2 + (Math.random() * 32 - 16) + 'px';
            floater.style.top = rect.top + 'px';
            floater.style.setProperty('--drift', (Math.random() * 120 - 60).toFixed(0) + 'px');
            floater.style.setProperty('--rise', (150 + Math.random() * 170).toFixed(0) + 'px');
            document.body.appendChild(floater);
            setTimeout(() => floater.remove(), 1700);
        }
    }

    function setupRadio() {
        const audio = new Audio();
        audio.preload = 'none';
        audio.src = window.PANDA_CHAT_ROOM_RADIO_URL || '';
        audio.volume = 0.7;

        const play = document.getElementById('chatRoomPlay');
        const stop = document.getElementById('chatRoomStop');
        const volume = document.getElementById('chatRoomVolume');
        const status = document.getElementById('chatRoomRadioStatus');

        play?.addEventListener('click', () => {
            if (!audio.paused) {
                audio.pause();
                play.innerHTML = '<i class="fas fa-play"></i>';
                status.textContent = 'Pausado';
                return;
            }
            audio.play().then(() => {
                play.innerHTML = '<i class="fas fa-pause"></i>';
                status.textContent = 'Reproduciendo en vivo';
            }).catch(() => {
                status.textContent = 'Toca play otra vez para escuchar';
            });
        });

        stop?.addEventListener('click', () => {
            audio.pause();
            audio.currentTime = 0;
            play.innerHTML = '<i class="fas fa-play"></i>';
            status.textContent = 'Detenido';
        });

        volume?.addEventListener('input', () => {
            audio.volume = Number(volume.value || 70) / 100;
        });
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    els.saveName?.addEventListener('click', saveName);
    els.form?.addEventListener('submit', sendMessage);
    root.addEventListener('click', (event) => {
        const emoji = event.target.closest('[data-room-emoji]');
        if (emoji) {
            insertEmoji(emoji.dataset.roomEmoji);
            return;
        }

        const reaction = event.target.closest('[data-room-reaction]');
        if (reaction) {
            sendReaction(reaction.dataset.roomReaction, reaction);
            return;
        }

        const pollOption = event.target.closest('[data-room-poll-option]');
        if (pollOption) {
            votePoll(pollOption.dataset.roomPollOption);
        }
    });
})();
