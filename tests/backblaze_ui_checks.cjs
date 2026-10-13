const { JSDOM } = require('jsdom');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const code = fs.readFileSync(path.join(__dirname, '../assets/js/backblaze-upload.js'), 'utf8');
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(predicate) {
    for (let i = 0; i < 500; i++) { if (predicate()) return; await pause(5); }
    throw new Error('Timed out waiting for UI.');
}
function setup({ token = true, fail = false, hold = false } = {}) {
    const dom = new JSDOM('<div id="itemModal" class="show"><form id="itemForm"><div><input name="url" value="old.mp3"></div><div><input name="cover" value="https://old.example/cover.jpg"></div><input name="sizeMB" value="1"><button type="submit">Guardar</button></form></div>', { url: 'https://example.com/dashboard.php', runScripts: 'outside-only' });
    const w = dom.window;
    Object.defineProperty(w, 'isSecureContext', { value: true });
    if (token) w.pandaB2Csrf = 'csrf-token';
    const calls = [];
    const pending = [];
    let copied = '';
    let serial = 0;
    const kinds = new Map();
    const xhrs = [];
    w.setTimeout = (callback) => setTimeout(callback, 1); // Speed up retry delays in this fixture.
    Object.defineProperty(w.navigator, 'clipboard', { value: { writeText: async value => { copied = value; } } });
    w.fetch = async (_, options) => {
        const payload = JSON.parse(options.body);
        calls.push(payload);
        assert.equal(payload.csrf, 'csrf-token');
        assert.equal(options.credentials, 'same-origin');
        let result = { success: true };
        if (payload.action === 'init') {
            const id = 'job-' + ++serial;
            kinds.set(id, payload.kind);
            result = { success: true, id, parts: 1, part_size: 16777216, mode: 'single', mime: payload.kind === 'audio' ? 'audio/mpeg' : 'image/png' };
        }
        if (payload.action === 'part') result.url = 'https://s3.us-east-005.backblazeb2.com/signed-object';
        if (payload.action === 'complete') result = { success: true, size_mb: 2, url: 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/' + (kinds.get(payload.id) === 'audio' ? 'MIXES/new.mp3' : 'PORTADAS/new.png') };
        return { ok: true, json: async () => result };
    };
    w.XMLHttpRequest = class {
        constructor() { this.upload = {}; xhrs.push(this); }
        open(method, url) { assert.equal(method, 'PUT'); assert.match(url, /^https:\/\/s3\./); }
        setRequestHeader(name, value) { assert.equal(name, 'Content-Type'); assert.ok(value.startsWith('audio/') || value.startsWith('image/')); }
        send(blob) {
            this.finish = () => {
                this.upload.onprogress?.({ lengthComputable: true, loaded: blob.size });
                this.status = fail ? 403 : 200;
                this.onload();
            };
            if (hold) pending.push(this); else setTimeout(() => this.finish(), 1);
        }
        abort() { this.onabort?.(); }
    };
    let saves = 0;
    const form = w.document.getElementById('itemForm');
    form.addEventListener('submit', e => { e.preventDefault(); saves++; });
    w.eval(code);
    function choose(index, name, mime) {
        const input = form.querySelectorAll('[type=file]')[index];
        Object.defineProperty(input, 'files', { configurable: true, value: [new w.File(['sample'], name, { type: mime })] });
        input.dispatchEvent(new w.Event('change'));
    }
    return { dom, w, calls, pending, form, choose, xhrs, copied: () => copied, saves: () => saves };
}
(async () => {
    const noRole = setup({ token: false });
    assert.equal(noRole.form.querySelectorAll('[type=file]').length, 0);
    await pause(5);
    noRole.dom.window.close();

    const success = setup({ hold: true });
    assert.equal(success.form.querySelectorAll('[type=file]').length, 2);
    success.choose(0, 'mix.mp3', 'audio/mpeg');
    success.choose(1, 'cover.png', 'image/png');
    await until(() => success.pending.length === 2);
    success.form.dispatchEvent(new success.w.Event('submit', { bubbles: true, cancelable: true }));
    assert.equal(success.saves(), 0, 'Existing submit handler ran during upload.');
    for (const xhr of success.pending) xhr.finish();
    await until(() => success.form.querySelector('[name=cover]').value.endsWith('/PORTADAS/new.png'));
    assert.ok(success.form.querySelector('[name=url]').value.endsWith('/MIXES/new.mp3'));
    assert.equal(success.form.querySelector('[name=sizeMB]').value, '2');
    assert.equal(success.form.querySelector('[name=url]').readOnly, false);
    assert.equal(success.calls.filter(x => x.action === 'complete').length, 2);
    const copy = [...success.form.querySelectorAll('button')].find(x => x.textContent === 'Copiar enlace');
    copy.click();
    await until(() => success.copied().endsWith('/MIXES/new.mp3'));
    success.form.dispatchEvent(new success.w.Event('submit', { bubbles: true, cancelable: true }));
    assert.equal(success.saves(), 1, 'Existing save flow did not resume.');
    await pause(5);
    success.dom.window.close();

    const failure = setup({ fail: true });
    failure.choose(0, 'mix.mp3', 'audio/mpeg');
    await until(() => failure.calls.some(x => x.action === 'abort'));
    assert.equal(failure.xhrs.length, 3, 'Failed parts did not retry.');
    assert.equal(failure.form.querySelector('[name=url]').value, 'old.mp3');
    assert.equal(failure.calls.filter(x => x.action === 'complete').length, 0);
    await until(() => !failure.form.querySelector('[type=file]').disabled);
    await pause(5);
    failure.dom.window.close();

    const cancelled = setup({ hold: true });
    cancelled.choose(0, 'mix.mp3', 'audio/mpeg');
    await until(() => cancelled.pending.length === 1);
    cancelled.w.document.getElementById('itemModal').classList.remove('show');
    await until(() => cancelled.calls.some(x => x.action === 'abort'));
    assert.equal(cancelled.form.querySelector('[name=url]').value, 'old.mp3');
    assert.equal(cancelled.calls.filter(x => x.action === 'complete').length, 0);
    await pause(5);
    cancelled.dom.window.close();
    console.log('DOM upload controls, concurrent audio/cover, progress, copied URL, save blocking, failure retry and modal cancellation checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
