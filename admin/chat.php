<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$sessionRole = $_SESSION['user_role'] ?? '';
if (!in_array($sessionRole, ['superadmin', 'admin', 'chat_moderator'], true)) {
    http_response_code(403);
    echo 'Acceso denegado.';
    exit;
}

$isSuperAdmin = $sessionRole === 'superadmin';
$isSystemAdmin = in_array($sessionRole, ['superadmin', 'admin'], true);
$isChatModerator = $sessionRole === 'chat_moderator';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderacion del Chat - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #e1261d; }
        body { background: #080808; color: #fff; font-family: Inter, system-ui, sans-serif; }
        .panel { background: #111; border: 1px solid rgba(255,255,255,.08); border-radius: 8px; }
        .field { width: 100%; background: #191919; border: 1px solid rgba(255,255,255,.1); border-radius: 8px; padding: 10px 12px; outline: none; }
        .btn { min-height: 38px; padding: 0 12px; border-radius: 8px; background: #262626; color: #fff; }
        .btn-primary { background: var(--primary); }
        .btn-danger { background: #b91c1c; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 7px; border-radius: 999px; background: rgba(255,255,255,.08); font-size: 12px; }
    </style>
</head>
<body>
    <main class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-black">Moderacion del chat en vivo</h1>
                <p class="text-neutral-400 text-sm mt-1"><?php echo $isChatModerator ? 'Modera la sala, responde a oyentes y controla mensajes desde aqui.' : 'Banea usuarios, borra mensajes, ajusta reglas y envia privados desde aqui.'; ?></p>
            </div>
            <div class="flex gap-2">
                <?php if (!$isChatModerator): ?>
                <a href="../dashboard.php" class="btn inline-flex items-center gap-2"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <?php endif; ?>
                <a href="../index.php#radio" class="btn inline-flex items-center gap-2"><i class="fas fa-radio"></i> Ver radio</a>
                <a href="../logout.php" class="btn inline-flex items-center gap-2"><i class="fas fa-right-from-bracket"></i> Salir</a>
            </div>
        </div>

        <section class="grid lg:grid-cols-[380px_1fr] gap-4">
            <div class="space-y-4">
                <div class="panel p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-bold">Reglas de sala</h2>
                        <span id="chat-status" class="badge">Cargando</span>
                    </div>
                    <label class="flex items-center gap-2 text-sm mb-3">
                        <input type="checkbox" id="chat-enabled" class="accent-red-600">
                        Chat activo
                    </label>
                    <label class="block text-sm text-neutral-300 mb-1">Mensaje de bienvenida</label>
                    <input id="welcome-message" class="field mb-3" maxlength="300">
                    <label class="block text-sm text-neutral-300 mb-1">Titulo en vivo</label>
                    <input id="live-title" class="field mb-3" maxlength="120" placeholder="Ahora en vivo">
                    <label class="block text-sm text-neutral-300 mb-1">DJ / anfitrión</label>
                    <input id="live-host" class="field mb-3" maxlength="120" placeholder="DJ actual">
                    <label class="block text-sm text-neutral-300 mb-1">Programa / complacencias</label>
                    <input id="live-program" class="field mb-3" maxlength="120" placeholder="Complacencias en vivo">
                    <label class="block text-sm text-neutral-300 mb-1">Anuncio fijado arriba del chat</label>
                    <textarea id="pinned-announcement" class="field min-h-[82px] mb-3" maxlength="500" placeholder="Ejemplo: Hoy en vivo desde las 8:00 PM"></textarea>
                    <label class="block text-sm text-neutral-300 mb-1">Reglas visibles</label>
                    <textarea id="chat-rules" class="field min-h-[110px]" maxlength="1200"></textarea>
                    <?php if ($isSuperAdmin): ?>
                    <button id="save-settings" class="btn btn-primary w-full mt-3"><i class="fas fa-save mr-2"></i>Guardar reglas</button>
                    <?php else: ?>
                    <p class="text-xs text-neutral-500 mt-3">Solo el superadmin puede modificar reglas y estado del chat.</p>
                    <?php endif; ?>
                </div>

                <div class="panel p-4">
                    <h2 class="font-bold mb-3">Reacciones</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-xs text-neutral-500 mb-2">Hoy</div>
                            <div id="reactions-today" class="space-y-2"></div>
                        </div>
                        <div>
                            <div class="text-xs text-neutral-500 mb-2">Total</div>
                            <div id="reactions-total" class="space-y-2"></div>
                        </div>
                    </div>
                </div>

                <div class="panel p-4">
                    <h2 class="font-bold mb-3">Encuesta rapida</h2>
                    <div id="active-poll" class="text-sm text-neutral-300 mb-3"></div>
                    <label class="block text-sm text-neutral-300 mb-1">Pregunta</label>
                    <input id="poll-question" class="field mb-3" maxlength="180" placeholder="Que genero quieres escuchar?">
                    <label class="block text-sm text-neutral-300 mb-1">Opciones</label>
                    <input id="poll-options" class="field mb-3" placeholder="Reggaeton, Salsa, Tipico, Retro">
                    <div class="flex gap-2">
                        <button id="create-poll" class="btn btn-primary flex-1"><i class="fas fa-square-poll-vertical mr-2"></i>Lanzar</button>
                        <button id="close-poll" class="btn flex-1">Cerrar</button>
                    </div>
                </div>

                <div class="panel p-4">
                    <h2 class="font-bold mb-3">Aviso a celulares</h2>
                    <label class="block text-sm text-neutral-300 mb-1">Titulo</label>
                    <input id="push-title" class="field mb-3" maxlength="120" value="Ya estamos transmitiendo en vivo">
                    <label class="block text-sm text-neutral-300 mb-1">Mensaje</label>
                    <textarea id="push-body" class="field min-h-[76px] mb-3" maxlength="240">Entra y escuchanos en Panda Truck Reloaded.</textarea>
                    <label class="block text-sm text-neutral-300 mb-1">Destino</label>
                    <select id="push-url" class="field mb-3">
                        <option value="index.php#radio">Radio</option>
                        <option value="sala-chat.php">Sala de chat</option>
                    </select>
                    <button id="send-push" class="btn btn-primary w-full"><i class="fas fa-bell mr-2"></i>Enviar aviso</button>
                    <p id="push-result" class="text-xs text-neutral-500 mt-2">Llega a celulares que instalaron la app y activaron avisos.</p>
                </div>

                <div class="panel p-4" id="users-panel">
                    <h2 class="font-bold mb-3">Usuarios recientes</h2>
                    <div id="users-list" class="space-y-2 max-h-[560px] overflow-y-auto"></div>
                </div>
            </div>

            <div class="panel p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="font-bold">Mensajes recientes</h2>
                    <div class="flex gap-2">
                        <?php if ($isSuperAdmin): ?>
                        <button id="clear-chat" class="btn btn-danger"><i class="fas fa-broom mr-2"></i>Limpiar chat</button>
                        <?php endif; ?>
                        <button id="refresh" class="btn"><i class="fas fa-rotate"></i></button>
                    </div>
                </div>
                <div class="rounded-lg border border-red-900/50 bg-red-950/20 p-3 mb-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div>
                            <h3 class="font-bold text-sm">Escribir en sala</h3>
                            <p class="text-xs text-neutral-400">
                                <?php echo $isSuperAdmin ? 'Saldras con corona de Super Admin.' : ($isChatModerator ? 'Saldras con estrella de Moderador.' : 'Saldras con estrella de Admin.'); ?>
                            </p>
                        </div>
                        <span class="badge text-yellow-300">
                            <i class="fas <?php echo $isSuperAdmin ? 'fa-crown' : 'fa-star'; ?>"></i>
                            <?php echo $isSuperAdmin ? 'Super Admin' : ($isChatModerator ? 'Moderador' : 'Admin'); ?>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input id="admin-chat-message" class="field" maxlength="500" placeholder="Mensaje publico para la sala">
                        <button id="send-admin-message" class="btn btn-primary shrink-0"><i class="fas fa-paper-plane mr-2"></i>Enviar</button>
                    </div>
                    <p class="text-xs text-neutral-500 mt-2">Los oyentes lo veran en el chat con tu insignia oficial.</p>
                </div>
                <div id="messages-list" class="space-y-2"></div>
            </div>
        </section>
    </main>

    <script>
        const isSuperAdmin = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
        const isSystemAdmin = <?php echo $isSystemAdmin ? 'true' : 'false'; ?>;
        const isChatModerator = <?php echo $isChatModerator ? 'true' : 'false'; ?>;
        const api = '../api/chat/admin.php';
        let users = [];
        let settingsDirty = false;
        const settingsFields = [
            'chat-enabled',
            'chat-rules',
            'welcome-message',
            'live-title',
            'live-host',
            'live-program',
            'pinned-announcement'
        ];

        function post(action, payload = {}) {
            return fetch(api + '?action=' + encodeURIComponent(action), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json());
        }

        function loadOverview() {
            fetch(api + '?action=overview')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return alert(data.error || 'No se pudo cargar.');
                    users = data.users || [];
                    updateSettingsFields(data.settings || {});
                    document.getElementById('chat-status').textContent = data.settings.enabled ? 'Activo' : 'Apagado';
                    renderActivePoll(data.active_poll);
                    renderReactions('reactions-today', data.reaction_today || []);
                    renderReactions('reactions-total', data.reaction_totals || []);
                    renderUsers(users);
                    renderMessages(data.messages || []);
                });
        }

        function updateSettingsFields(settings) {
            const activeId = document.activeElement ? document.activeElement.id : '';
            if (settingsDirty || settingsFields.includes(activeId)) {
                return;
            }

            document.getElementById('chat-enabled').checked = !!settings.enabled;
            document.getElementById('chat-rules').value = settings.rules || '';
            document.getElementById('welcome-message').value = settings.welcome_message || '';
            document.getElementById('live-title').value = settings.live_title || '';
            document.getElementById('live-host').value = settings.live_host || '';
            document.getElementById('live-program').value = settings.live_program || '';
            document.getElementById('pinned-announcement').value = settings.pinned_announcement || '';
        }

        function renderReactions(targetId, items) {
            const box = document.getElementById(targetId);
            if (!box) return;
            if (!items.length) {
                box.innerHTML = '<p class="text-neutral-500 text-sm">0</p>';
                return;
            }
            box.innerHTML = items.slice(0, 8).map(item => `
                <div class="flex items-center justify-between rounded-lg bg-neutral-900 border border-white/5 px-3 py-2">
                    <span class="text-xl">${escapeHtml(item.reaction)}</span>
                    <strong>${Number(item.total || 0)}</strong>
                </div>
            `).join('');
        }

        function renderActivePoll(poll) {
            const box = document.getElementById('active-poll');
            if (!box) return;
            if (!poll) {
                box.innerHTML = '<p class="text-neutral-500">No hay encuesta activa.</p>';
                return;
            }
            box.innerHTML = `
                <div class="rounded-lg bg-neutral-900 border border-white/5 p-3">
                    <div class="font-bold mb-2">${escapeHtml(poll.question)}</div>
                    ${(poll.options || []).map(option => `
                        <div class="flex justify-between text-xs border-t border-white/5 py-1">
                            <span>${escapeHtml(option.option_text)}</span>
                            <strong>${Number(option.votes || 0)}</strong>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function renderUsers(items) {
            const box = document.getElementById('users-list');
            if (!items.length) {
                box.innerHTML = '<p class="text-neutral-500 text-sm">No hay usuarios recientes.</p>';
                return;
            }
            box.innerHTML = items.map(user => {
                const roleIcon = user.role === 'chat_admin' ? '<i class="fas fa-star text-yellow-400"></i>' : '';
                const banLabel = Number(user.is_banned) === 1 ? 'Quitar ban' : 'Banear';
                const banAction = Number(user.is_banned) === 1 ? 'unban_user' : 'ban_user';
                const roleButton = isSuperAdmin
                    ? `<button class="btn" onclick="setRole(${user.id}, '${user.role === 'chat_admin' ? 'viewer' : 'chat_admin'}')">${user.role === 'chat_admin' ? 'Quitar estrella' : 'Dar estrella'}</button>`
                    : '';
                const privateButton = isSuperAdmin
                    ? `<button class="btn btn-primary" onclick="privateMessage(${user.id})"><i class="fas fa-envelope"></i></button>`
                    : '';
                const renameButton = isSystemAdmin
                    ? `<button class="btn" onclick="renameUser(${user.id})">Cambiar nombre</button>`
                    : '';
                return `
                    <div class="user-card p-3 rounded-lg bg-neutral-900 border border-white/5" data-user-id="${user.id}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-bold truncate">${roleIcon} ${escapeHtml(user.nickname)}</div>
                                <div class="text-xs text-neutral-500 truncate">${escapeHtml(user.last_seen || '')}</div>
                                ${Number(user.is_banned) === 1 ? `<div class="text-xs text-red-400 mt-1">${escapeHtml(user.banned_reason || 'Baneado')}</div>` : ''}
                            </div>
                            ${privateButton}
                        </div>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <button class="btn ${Number(user.is_banned) === 1 ? '' : 'btn-danger'}" onclick="${banAction}(${user.id})">${banLabel}</button>
                            ${renameButton}
                            ${roleButton}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderMessages(items) {
            const box = document.getElementById('messages-list');
            if (!items.length) {
                box.innerHTML = '<p class="text-neutral-500 text-sm">No hay mensajes.</p>';
                return;
            }
            box.innerHTML = items.map(message => {
                const icon = message.role === 'superadmin' ? '<i class="fas fa-crown text-yellow-400"></i>' : (message.role === 'admin' || message.role === 'chat_admin' || message.role === 'chat_moderator') ? '<i class="fas fa-star text-yellow-400"></i>' : '';
                const type = message.message_type === 'private' ? '<span class="badge text-yellow-300">Privado</span>' : '';
                const deleted = Number(message.is_deleted) === 1 ? '<span class="badge text-red-300">Borrado</span>' : '';
                const featured = Number(message.is_featured) === 1 ? '<span class="badge text-yellow-300">Destacado</span>' : '';
                const featureLabel = Number(message.is_featured) === 1 ? 'Quitar destacado' : 'Destacar';
                const featureValue = Number(message.is_featured) === 1 ? 0 : 1;
                const userJump = message.user_id ? `<button class="hover:text-red-300 underline decoration-dotted" onclick="focusUser(${message.user_id})">${escapeHtml(message.nickname)}</button>` : escapeHtml(message.nickname);
                return `
                    <div class="p-3 rounded-lg bg-neutral-900 border border-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold">${icon} ${userJump} ${type} ${deleted} ${featured}</div>
                                <div class="text-neutral-200 mt-1 break-words">${escapeHtml(message.message)}</div>
                                <div class="text-xs text-neutral-500 mt-1">${escapeHtml(message.created_at || '')}</div>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <button class="btn" onclick="featureMessage(${message.id}, ${featureValue})">${featureLabel}</button>
                                <button class="btn btn-danger" onclick="deleteMessage(${message.id})"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function ban_user(id) {
            const reason = prompt('Motivo del ban:', 'No cumplio las reglas de la sala.');
            if (reason === null) return;
            post('ban_user', { id, reason }).then(loadOverview);
        }

        function unban_user(id) {
            post('unban_user', { id }).then(loadOverview);
        }

        function setRole(id, role) {
            post('set_role', { id, role }).then(loadOverview);
        }

        function renameUser(id) {
            const user = users.find(item => Number(item.id) === Number(id));
            const currentName = user ? user.nickname : '';
            const nickname = prompt('Nuevo nombre para este usuario:', currentName);
            if (nickname === null) return;
            const clean = nickname.trim();
            if (!clean) return alert('El nombre no puede estar vacio.');
            post('rename_user', { id, nickname: clean }).then(result => {
                if (!result.success) return alert(result.error || 'No se pudo cambiar el nombre.');
                loadOverview();
            });
        }

        function focusUser(id) {
            const panel = document.getElementById('users-panel');
            const list = document.getElementById('users-list');
            const card = document.querySelector(`[data-user-id="${id}"]`);
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (card && list) {
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.add('ring-2', 'ring-red-500');
                    setTimeout(() => card.classList.remove('ring-2', 'ring-red-500'), 2200);
                }, 250);
            } else {
                alert('Ese usuario no esta en la lista reciente. Usa buscar por mensajes o espera que vuelva a escribir.');
            }
        }

        function deleteMessage(id) {
            if (!confirm('Borrar este mensaje del chat?')) return;
            post('delete_message', { id }).then(loadOverview);
        }

        function featureMessage(id, featured) {
            post('feature_message', { id, featured }).then(result => {
                if (!result.success) return alert(result.error || 'No se pudo destacar el mensaje.');
                loadOverview();
            });
        }

        function clearChat() {
            if (!confirm('Esto ocultara todos los mensajes del chat. Los usuarios y estadisticas se mantienen. Continuar?')) return;
            if (!confirm('Confirmacion final: limpiar toda la sala de chat?')) return;
            post('clear_messages', {}).then(result => {
                if (!result.success) return alert(result.error || 'No se pudo limpiar el chat.');
                loadOverview();
            });
        }

        function privateMessage(id) {
            const user = users.find(item => Number(item.id) === Number(id));
            const nickname = user ? user.nickname : 'usuario';
            const message = prompt('Mensaje privado para ' + nickname + ':');
            if (!message) return;
            post('private_message', { recipient_id: id, message }).then(loadOverview);
        }

        function sendAdminMessage() {
            const input = document.getElementById('admin-chat-message');
            const message = input ? input.value.trim() : '';
            if (!message) return alert('Escribe un mensaje para la sala.');

            post('admin_public_message', { message }).then(result => {
                if (!result.success) return alert(result.error || 'No se pudo enviar el mensaje.');
                input.value = '';
                loadOverview();
            });
        }

        function sendPushNotice() {
            const resultBox = document.getElementById('push-result');
            const payload = {
                title: document.getElementById('push-title').value.trim(),
                body: document.getElementById('push-body').value.trim(),
                url: document.getElementById('push-url').value,
                requireInteraction: true
            };
            if (!payload.title || !payload.body) {
                alert('Titulo y mensaje son requeridos.');
                return;
            }
            if (resultBox) resultBox.textContent = 'Enviando aviso...';
            fetch('../api/push_send.php?action=send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(result => {
                if (!result.success) {
                    if (resultBox) resultBox.textContent = result.error || 'No se pudo enviar.';
                    return;
                }
                if (resultBox) resultBox.textContent = `Aviso enviado: ${result.sent} enviados, ${result.failed} fallidos.`;
            }).catch(() => {
                if (resultBox) resultBox.textContent = 'Error de conexion al enviar.';
            });
        }

        document.getElementById('refresh').addEventListener('click', loadOverview);
        document.getElementById('clear-chat')?.addEventListener('click', clearChat);
        document.getElementById('send-admin-message')?.addEventListener('click', sendAdminMessage);
        document.getElementById('send-push')?.addEventListener('click', sendPushNotice);
        document.getElementById('admin-chat-message')?.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendAdminMessage();
            }
        });
        settingsFields.forEach(id => {
            const field = document.getElementById(id);
            field?.addEventListener('input', () => {
                settingsDirty = true;
            });
            field?.addEventListener('change', () => {
                settingsDirty = true;
            });
        });

        document.getElementById('save-settings')?.addEventListener('click', () => {
            post('save_settings', {
                enabled: document.getElementById('chat-enabled').checked ? 1 : 0,
                rules: document.getElementById('chat-rules').value,
                welcome_message: document.getElementById('welcome-message').value,
                live_title: document.getElementById('live-title').value,
                live_host: document.getElementById('live-host').value,
                live_program: document.getElementById('live-program').value,
                pinned_announcement: document.getElementById('pinned-announcement').value
            }).then(result => {
                if (!result.success) return alert(result.error || 'No se pudo guardar.');
                settingsDirty = false;
                loadOverview();
            });
        });

        document.getElementById('create-poll')?.addEventListener('click', () => {
            const question = document.getElementById('poll-question').value.trim();
            const options = document.getElementById('poll-options').value.split(',').map(v => v.trim()).filter(Boolean);
            post('create_poll', { question, options }).then(result => {
                if (!result.success) return alert(result.error || 'No se pudo crear la encuesta.');
                document.getElementById('poll-question').value = '';
                document.getElementById('poll-options').value = '';
                loadOverview();
            });
        });

        document.getElementById('close-poll')?.addEventListener('click', () => {
            post('close_poll', {}).then(loadOverview);
        });

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }

        loadOverview();
        setInterval(loadOverview, 5000);
    </script>
</body>
</html>
