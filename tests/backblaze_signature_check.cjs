// Verify PHP SigV4 output with an independent Node implementation of AWS SigV4.
const { execFileSync } = require('node:child_process');
const { createHash, createHmac } = require('node:crypto');
const assert = require('node:assert/strict');
const path = require('node:path');
const phpFile = path.resolve(__dirname, '../includes/backblaze_upload.php').replaceAll('\\', '/');
const php = `require '${phpFile}'; echo panda_b2_presign(['bucket'=>'DJIMMY-PANDA','region'=>'us-east-005','key_id'=>'TESTKEY','secret'=>'test-secret'], 'PUT', 'MIXES/a b+.mp3', ['uploadId'=>'a+b/c=', 'partNumber'=>2], 1700000000);`;
const url = new URL(execFileSync('php', ['-r', php], { encoding: 'utf8' }));
const params = url.searchParams;
const signature = params.get('X-Amz-Signature');
params.delete('X-Amz-Signature');
const encode = s => encodeURIComponent(s).replace(/[!'()*]/g, c => '%' + c.charCodeAt(0).toString(16).toUpperCase());
// Canonical sorting is bytewise (uppercase precedes lowercase).
const canonicalQuery = [...params].sort((a, b) => a[0] < b[0] ? -1 : a[0] > b[0] ? 1 : 0)
    .map(([k, v]) => encode(k) + '=' + encode(v)).join('&');
const scope = params.get('X-Amz-Credential').split('/').slice(1).join('/');
const date = params.get('X-Amz-Date');
const canonical = ['PUT', '/DJIMMY-PANDA/MIXES/a%20b%2B.mp3', canonicalQuery, 'host:' + url.host + '\n', 'host', 'UNSIGNED-PAYLOAD'].join('\n');
const hmac = (key, value) => createHmac('sha256', key).update(value).digest();
let key = hmac('AWS4test-secret', date.slice(0, 8));
key = hmac(key, 'us-east-005');
key = hmac(key, 's3');
key = hmac(key, 'aws4_request');
const expected = createHmac('sha256', key).update(['AWS4-HMAC-SHA256', date, scope, createHash('sha256').update(canonical).digest('hex')].join('\n')).digest('hex');
assert.equal(signature, expected);
assert.equal(url.host, 's3.us-east-005.backblazeb2.com');
assert.equal(params.get('uploadId'), 'a+b/c=');
assert.equal(params.get('X-Amz-Expires'), '3600');
assert.ok(!url.href.includes('test-secret'));
console.log('Independent PHP/Node SigV4 check passed.');
