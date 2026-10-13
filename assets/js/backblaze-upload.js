/* Direct uploads: the server signs a single object/part; no permanent key reaches this script. */
(() => {
    'use strict';
    const csrf = window.pandaB2Csrf;
    if (!csrf) return;
    const jobs = new Set();
    const attached = new WeakSet();
    const formats = {
        audio: '.mp3,.m4a,.wav,.ogg,.flac,.aac',
        image: '.jpg,.jpeg,.png,.webp,.gif'
    };
    const api = async (payload) => {
        const response = await fetch('api/backblaze_upload.php', {
            method: 'POST', credentials: 'same-origin', cache: 'no-store',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...payload, csrf })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.error || 'No se pudo procesar la subida.');
        return result;
    };
    const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
    const put = (job, url, blob, mime, onProgress) => new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        job.xhr = xhr;
        xhr.open('PUT', url);
        if (mime) xhr.setRequestHeader('Content-Type', mime);
        xhr.upload.onprogress = e => { if (e.lengthComputable) onProgress(e.loaded); };
        xhr.onload = () => {
            job.xhr = null;
            if (xhr.status >= 200 && xhr.status < 300) resolve();
            else reject(new Error('Backblaze rechazo una parte. Comprueba la configuracion CORS y los permisos.'));
        };
        xhr.onerror = () => { job.xhr = null; reject(new Error('Error de red al subir. Comprueba la conexion o CORS.')); };
        xhr.onabort = () => { job.xhr = null; reject(new Error('Subida cancelada.')); };
        xhr.send(blob);
    });
    function widget(form, target, kind) {
        if (!target || attached.has(target)) return;
        attached.add(target);
        const box = document.createElement('div');
        box.className = 'mt-2 p-3 bg-neutral-900 border border-neutral-700 rounded space-y-2';
        const label = document.createElement('label');
        label.className = 'block text-sm';
        label.textContent = kind === 'audio' ? 'Subir musica a Backblaze (MIXES)' : 'Subir portada a Backblaze (PORTADAS)';
        const chooser = document.createElement('input');
        chooser.type = 'file';
        chooser.accept = formats[kind];
        chooser.className = 'block w-full text-sm';
        label.appendChild(chooser);
        const progress = document.createElement('progress');
        progress.max = 100;
        progress.value = 0;
        progress.className = 'w-full';
        progress.hidden = true;
        const status = document.createElement('p');
        status.className = 'text-xs text-neutral-400';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        status.textContent = 'Tambien puedes seguir pegando el enlace manualmente.';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'p-2 bg-neutral-700 rounded text-sm';
        cancel.textContent = 'Cancelar subida';
        cancel.hidden = true;
        const result = document.createElement('div');
        result.hidden = true;
        const resultLabel = document.createElement('label');
        resultLabel.className = 'block text-xs';
        resultLabel.textContent = 'Friendly URL';
        const link = document.createElement('input');
        link.type = 'url';
        link.readOnly = true;
        link.className = 'w-full p-2 bg-neutral-800 rounded text-xs';
        resultLabel.appendChild(link);
        const copy = document.createElement('button');
        copy.type = 'button';
        copy.className = 'mt-2 p-2 bg-neutral-700 rounded text-sm';
        copy.textContent = 'Copiar enlace';
        result.append(resultLabel, copy);
        box.append(label, progress, status, cancel, result);
        target.parentElement.appendChild(box);
        let current = null;
        copy.addEventListener('click', async () => {
            try {
                if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(link.value);
                else {
                    link.focus(); link.select();
                    if (!document.execCommand('copy')) throw new Error('copy');
                }
                status.textContent = 'Enlace copiado.';
            } catch (_) { link.focus(); link.select(); status.textContent = 'Selecciona el enlace y copialo manualmente.'; }
        });
        cancel.addEventListener('click', () => {
            if (current) { current.cancelled = true; current.xhr?.abort(); status.textContent = 'Cancelando subida...'; }
        });
        // Keep the existing submit handler and prevent saving a partially uploaded mix.
        form.addEventListener('submit', e => {
            if ([...jobs].some(job => job.form === form)) {
                e.preventDefault(); e.stopImmediatePropagation();
                status.textContent = 'Espera a que terminen las subidas antes de guardar.';
            }
        }, true);
        chooser.addEventListener('change', async () => {
            const file = chooser.files[0];
            if (!file || current) return;
            if (!file.size || file.size > 10000000000000) {
                status.textContent = 'Selecciona un archivo no vacio, de hasta 10 TB.';
                chooser.value = ''; return;
            }
            const job = { form, box, id: null, xhr: null, cancelled: false };
            current = job;
            jobs.add(job);
            chooser.disabled = true;
            target.readOnly = true;
            progress.hidden = false;
            progress.value = 0;
            result.hidden = true;
            cancel.hidden = false;
            status.textContent = 'Preparando subida de ' + file.name + '...';
            let completed = false;
            try {
                const plan = await api({ action: 'init', kind, name: file.name, size: file.size });
                job.id = plan.id;
                for (let part = 1; part <= plan.parts; part++) {
                    const offset = (part - 1) * plan.part_size;
                    const blob = file.slice(offset, Math.min(offset + plan.part_size, file.size));
                    for (let attempt = 0; ; attempt++) {
                        if (job.cancelled) throw new Error('Subida cancelada.');
                        try {
                            const signed = await api({ action: 'part', id: job.id, number: part });
                            if (job.cancelled) throw new Error('Subida cancelada.');
                            await put(job, signed.url, blob, plan.mode === 'single' ? plan.mime : null, loaded => {
                                progress.value = Math.min(99, (offset + loaded) / file.size * 100);
                                status.textContent = 'Subiendo ' + Math.floor(progress.value) + '% (parte ' + part + '/' + plan.parts + ')';
                            });
                            break;
                        } catch (error) {
                            if (job.cancelled || attempt >= 2) throw error;
                            status.textContent = 'Reintentando la parte ' + part + '...';
                            await delay(1000 * (attempt + 1));
                        }
                    }
                }
                if (job.cancelled) throw new Error('Subida cancelada.');
                status.textContent = 'Verificando el archivo en Backblaze...';
                let uploaded;
                for (let attempt = 0; ; attempt++) {
                    try { uploaded = await api({ action: 'complete', id: job.id }); break; }
                    catch (error) {
                        if (attempt >= 2 || job.cancelled) throw error;
                        await delay(1000 * (attempt + 1));
                    }
                }
                completed = true;
                if (job.cancelled || !box.isConnected) return;
                target.value = uploaded.url;
                target.dispatchEvent(new Event('input', { bubbles: true }));
                if (kind === 'audio') {
                    const size = form.querySelector('[name="sizeMB"]');
                    if (size) size.value = uploaded.size_mb;
                }
                link.value = uploaded.url;
                result.hidden = false;
                progress.value = 100;
                status.textContent = 'Archivo subido. Enlace listo; guarda el formulario para publicarlo en la web.';
            } catch (error) {
                status.textContent = job.cancelled ? 'Subida cancelada.' : error.message;
            } finally {
                if (job.id && !completed) {
                    try { await api({ action: 'abort', id: job.id }); }
                    catch (_) { status.textContent += ' No se pudo cancelar en Backblaze; revisa las subidas pendientes del bucket.'; }
                }
                jobs.delete(job);
                current = null;
                chooser.disabled = false;
                chooser.value = '';
                target.readOnly = false;
                cancel.hidden = true;
            }
        });
    }
    function attach() {
        const mix = document.getElementById('itemForm');
        if (mix && mix.querySelector('[name="sizeMB"]')) {
            widget(mix, mix.querySelector('[name="url"]'), 'audio');
            widget(mix, mix.querySelector('[name="cover"]'), 'image');
        }
        const album = document.getElementById('albumForm');
        if (album) widget(album, album.querySelector('[name="cover"]'), 'image');
        const song = document.getElementById('cancionForm');
        if (song) widget(song, song.querySelector('[name="url"]'), 'audio');
        for (const job of jobs) {
            if (!job.box.isConnected || !document.getElementById('itemModal')?.classList.contains('show')) {
                job.cancelled = true; job.xhr?.abort();
            }
        }
    }
    new MutationObserver(attach).observe(document.getElementById('itemModal'), { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    attach();
    window.addEventListener('beforeunload', e => {
        if (jobs.size) { e.preventDefault(); e.returnValue = ''; }
    });
})();
