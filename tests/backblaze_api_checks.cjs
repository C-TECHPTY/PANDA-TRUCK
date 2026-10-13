// Test the actual API entrypoint with an isolated database/session fixture.
const { spawn } = require('node:child_process');
const fs = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const assert = require('node:assert/strict');
(async () => {
    const root = await fs.mkdtemp(path.join(os.tmpdir(), 'panda-b2-api-check-'));
    await fs.mkdir(path.join(root, 'api'));
    await fs.mkdir(path.join(root, 'includes'));
    for (const file of ['api/backblaze_upload.php', 'includes/backblaze_upload.php']) {
        await fs.copyFile(path.join(__dirname, '..', file), path.join(root, file));
    }
    await fs.writeFile(path.join(root, 'includes/config.php'), `<?php
session_start();
$_SESSION['user_id'] = ($_GET['role'] ?? '') === 'anonymous' ? null : 7;
$_SESSION['user_role'] = 'superadmin'; // Deliberately forged/stale session role.
$_SESSION['b2_csrf'] = 'fixture-token';
class FixtureStatement {
    public function execute($args) {}
    public function fetch($mode) {
        return ['id' => 7, 'role' => $_GET['role'] ?? 'viewer', 'active' => (int)($_GET['active'] ?? 1)];
    }
}
class FixtureDB { public function prepare($query) { return new FixtureStatement(); } }
function getDB() { return new FixtureDB(); }
`);
    const port = await new Promise(resolve => {
        const socket = net.createServer();
        socket.listen(0, '127.0.0.1', () => {
            const port = socket.address().port;
            socket.close(() => resolve(port));
        });
    });
    const server = spawn('php', ['-S', '127.0.0.1:' + port, '-t', root], {
        stdio: 'ignore', env: { ...process.env, B2_UPLOAD_ENABLED: 'false' }
    });
    const base = 'http://127.0.0.1:' + port + '/api/backblaze_upload.php';
    try {
        for (let attempt = 0; attempt < 50; attempt++) {
            try { await fetch(base); break; }
            catch (_) { await new Promise(resolve => setTimeout(resolve, 100)); }
        }
        const get = await fetch(base);
        assert.equal(get.status, 405);
        const request = async (query, payload) => {
            const response = await fetch(base + query, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: typeof payload === 'string' ? payload : JSON.stringify(payload) });
            return { status: response.status, body: await response.json() };
        };
        assert.equal((await request('?role=anonymous', {})).status, 401);
        for (const role of ['dj', 'viewer', 'chat_moderator']) {
            assert.equal((await request('?role=' + role, { csrf: 'fixture-token', action: 'init' })).status, 403);
        }
        assert.equal((await request('?role=admin&active=0', { csrf: 'fixture-token' })).status, 403);
        assert.equal((await request('?role=admin', { csrf: 'forged' })).status, 403);
        assert.equal((await request('?role=admin', '{invalid')).status, 400);
        assert.equal((await request('?role=admin', 'x'.repeat(16385))).status, 413);
        for (const role of ['admin', 'superadmin']) {
            const result = await request('?role=' + role, { csrf: 'fixture-token', action: 'init', name: 'test.mp3', kind: 'audio', size: 1 });
            assert.equal(result.status, 503);
            assert.match(result.body.error, /no esta configurada/);
            assert.equal(result.body.success, false);
        }
        console.log('HTTP method, anonymous, current database role, inactive user, CSRF, request size and disabled-config checks passed.');
    } finally {
        server.kill();
        await new Promise(resolve => { if (server.exitCode !== null) resolve(); else server.once('exit', resolve); });
        // Only remove the exact mkdtemp fixture created above.
        await fs.rm(root, { recursive: true, force: true });
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
