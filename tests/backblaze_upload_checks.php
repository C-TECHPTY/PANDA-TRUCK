<?php
require_once __DIR__ . '/../includes/backblaze_upload.php';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function rejected($callback) {
    try { $callback(); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException('Expected rejected input.');
}
$session = ['user_id' => 7];
foreach (['admin', 'superadmin'] as $role) check(panda_b2_admin($session, ['id' => 7, 'role' => $role, 'active' => 1]), 'Admin denied.');
foreach (['dj', 'viewer', 'chat_moderator'] as $role) check(!panda_b2_admin($session, ['id' => 7, 'role' => $role, 'active' => 1]), 'Unauthorized role allowed.');
check(!panda_b2_admin([], ['id' => 7, 'role' => 'admin', 'active' => 1]), 'Anonymous allowed.');
check(!panda_b2_admin($session, ['id' => 8, 'role' => 'admin', 'active' => 1]), 'Wrong user allowed.');
check(!panda_b2_admin($session, ['id' => 7, 'role' => 'admin', 'active' => 0]), 'Inactive user allowed.');
foreach ([1, 16 * 1048576, 144 * 1048576, 6 * 1073741824, 10000000000000] as $size) {
    $plan = panda_b2_plan('../Mi MIX + ñ.MP3', 'audio', $size);
    check(preg_match('#^MIXES/[a-zA-Z0-9_-]+-[a-f0-9]{24}\\.mp3$#D', $plan['key']) === 1, 'Unsafe object key.');
    check($plan['parts'] <= 10000 && $plan['part_size'] <= 5 * 1073741824, 'Multipart bounds broken.');
    check($plan['mime'] === 'audio/mpeg', 'Incorrect media type.');
}
check(panda_b2_plan('cover.PNG', 'image', 123)['mime'] === 'image/png', 'Incorrect image type.');
check(strpos(panda_b2_plan('cover.PNG', 'image', 123)['key'], 'PORTADAS/') === 0, 'Wrong image prefix.');
check(panda_b2_plan('mix.mp3', 'audio', 1)['key'] !== panda_b2_plan('mix.mp3', 'audio', 1)['key'], 'Object collision.');
foreach ([0, -1, 10000000000001, '123', 12.5, null] as $size) rejected(function () use ($size) { panda_b2_plan('x.mp3', 'audio', $size); });
foreach (['cover.svg', 'cover.php', 'cover.html', 'cover.jpg.php'] as $name) rejected(function () use ($name) { panda_b2_plan($name, 'image', 123); });
rejected(function () { panda_b2_plan('x.mp3', '../../bucket', 123); });
$state = ['parts' => 2, 'part_size' => 16 * 1048576, 'size' => 16 * 1048576 + 1];
$parts = [['number' => 1, 'size' => 16 * 1048576, 'etag' => '"abc"'], ['number' => 2, 'size' => 1, 'etag' => '"def"']];
panda_b2_validate_parts($state, $parts);
rejected(function () use ($state, $parts) { panda_b2_validate_parts($state, array_reverse($parts)); });
rejected(function () use ($state, $parts) { panda_b2_validate_parts($state, [$parts[0]]); });
$parts[1]['size'] = 2;
rejected(function () use ($state, $parts) { panda_b2_validate_parts($state, $parts); });
$xml = panda_b2_xml('<InitiateMultipartUploadResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><UploadId>id-123</UploadId></InitiateMultipartUploadResult>');
check((string)$xml->UploadId === 'id-123', 'Default XML namespace is not parsed.');
check(panda_b2_query(['uploadId' => 'a+b/c=', 'partNumber' => 1]) === 'partNumber=1&uploadId=a%2Bb%2Fc%3D', 'Query encoding broken.');
$config = ['bucket' => 'DJIMMY-PANDA', 'region' => 'us-east-005', 'key_id' => 'TESTKEY', 'secret' => 'test-secret', 'friendly_base' => 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/'];
check(panda_b2_result($config, ['key' => 'MIXES/a b+.mp3', 'size' => 1])['url'] === 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/a%20b%2B.mp3', 'Friendly URL encoding broken.');
// The existing CDN must continue to normalize and play new links.
require_once __DIR__ . '/../includes/cdn.php';
check(cdn_normalize_audio_path('https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/x.mp3') === 'DJIMMY-PANDA/MIXES/x.mp3', 'CDN normalization changed.');
check(cdn_audio_url('DJIMMY-PANDA/MIXES/x.mp3') === 'https://panda-truck.b-cdn.net/DJIMMY-PANDA/MIXES/x.mp3', 'CDN playback changed.');
echo "Backblaze permission, filename, size, multipart, XML and CDN checks passed.\n";
