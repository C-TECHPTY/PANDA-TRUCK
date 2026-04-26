<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!$auth->isAdmin()) {
    http_response_code(403);
    die('Acceso denegado');
}

$db = getDB();
$message = '';
$error = '';
$details = [];

function dedupeNormalizeDjName($name) {
    $name = trim((string)$name);
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    if ($converted !== false) {
        $name = $converted;
    }

    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9]+/', '', $name);
    return $name ?: strtolower(trim((string)$name));
}

function dedupeHasColumn(PDO $db, $table, $column) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name
    ");
    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);
    return (int)$stmt->fetchColumn() > 0;
}

function dedupeTableExists(PDO $db, $table) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
    ");
    $stmt->execute([':table_name' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function dedupeGetDjs(PDO $db) {
    $stmt = $db->query("SELECT * FROM djs ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function dedupeBuildGroups(PDO $db) {
    $djs = dedupeGetDjs($db);
    $groups = [];

    foreach ($djs as $dj) {
        $key = dedupeNormalizeDjName($dj['name'] ?? '');
        if ($key === '') {
            continue;
        }
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $dj;
    }

    return array_filter($groups, function ($items) {
        return count($items) > 1;
    });
}

function dedupeScoreDj(PDO $db, array $dj) {
    $score = 0;
    if (!empty($dj['active'])) {
        $score += 1000;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM mixes WHERE dj = :name");
    $stmt->execute([':name' => $dj['name']]);
    $score += ((int)$stmt->fetchColumn()) * 100;

    $usefulFields = [
        'avatar', 'profile_photo', 'bio', 'biography', 'socials', 'email',
        'instagram', 'slug', 'genre', 'city'
    ];
    foreach ($usefulFields as $field) {
        if (isset($dj[$field]) && trim((string)$dj[$field]) !== '') {
            $score += 5;
        }
    }

    $score -= (int)$dj['id'] / 1000000;
    return $score;
}

function dedupeChooseKeeper(PDO $db, array $items) {
    usort($items, function ($a, $b) use ($db) {
        $scoreA = dedupeScoreDj($db, $a);
        $scoreB = dedupeScoreDj($db, $b);
        if ($scoreA === $scoreB) {
            return (int)$a['id'] <=> (int)$b['id'];
        }
        return $scoreA > $scoreB ? -1 : 1;
    });

    return $items[0];
}

function dedupeAddUniqueNameIndex(PDO $db) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'djs'
          AND INDEX_NAME = 'unique_dj_name'
    ");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $db->exec("ALTER TABLE djs ADD UNIQUE KEY unique_dj_name (name)");
    }
}

function dedupeMergeField(PDO $db, $keeperId, $field, $value) {
    if ($value === null || trim((string)$value) === '') {
        return;
    }

    $allowed = [
        'genre', 'city', 'bio', 'avatar', 'socials', 'email', 'instagram',
        'biography', 'profile_photo', 'slug'
    ];
    if (!in_array($field, $allowed, true)) {
        return;
    }

    $sql = "UPDATE djs SET `$field` = :value WHERE id = :id AND (`$field` IS NULL OR `$field` = '')";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':value' => $value,
        ':id' => $keeperId,
    ]);
}

function dedupeApply(PDO $db) {
    $groups = dedupeBuildGroups($db);
    if (!$groups) {
        return ['deleted' => 0, 'groups' => [], 'backup' => null];
    }

    $backupTable = 'djs_backup_dedupe_' . date('Ymd_His');

    $db->exec("CREATE TABLE `$backupTable` AS SELECT * FROM djs");

    $deleted = 0;
    $processed = [];

    foreach ($groups as $normalizedName => $items) {
        $keeper = dedupeChooseKeeper($db, $items);
        $keeperId = (int)$keeper['id'];
        $keeperName = $keeper['name'];
        $duplicateIds = [];

        foreach ($items as $dj) {
            $id = (int)$dj['id'];
            if ($id === $keeperId) {
                continue;
            }

            $duplicateIds[] = $id;
            foreach ($dj as $field => $value) {
                dedupeMergeField($db, $keeperId, $field, $value);
            }

            if (dedupeTableExists($db, 'users') && dedupeHasColumn($db, 'users', 'dj_id')) {
                $stmt = $db->prepare("UPDATE users SET dj_id = :keeper_id WHERE dj_id = :duplicate_id");
                $stmt->execute([':keeper_id' => $keeperId, ':duplicate_id' => $id]);
            }
            if (dedupeTableExists($db, 'site_visits') && dedupeHasColumn($db, 'site_visits', 'dj_id')) {
                $stmt = $db->prepare("UPDATE site_visits SET dj_id = :keeper_id WHERE dj_id = :duplicate_id");
                $stmt->execute([':keeper_id' => $keeperId, ':duplicate_id' => $id]);
            }
            if (dedupeTableExists($db, 'dj_payments') && dedupeHasColumn($db, 'dj_payments', 'dj_id')) {
                $stmt = $db->prepare("UPDATE dj_payments SET dj_id = :keeper_id WHERE dj_id = :duplicate_id");
                $stmt->execute([':keeper_id' => $keeperId, ':duplicate_id' => $id]);
            }

            $stmt = $db->prepare("UPDATE mixes SET dj = :keeper_name WHERE dj = :duplicate_name");
            $stmt->execute([':keeper_name' => $keeperName, ':duplicate_name' => $dj['name']]);

            if (dedupeTableExists($db, 'videos')) {
                $stmt = $db->prepare("UPDATE videos SET dj = :keeper_name WHERE dj = :duplicate_name");
                $stmt->execute([':keeper_name' => $keeperName, ':duplicate_name' => $dj['name']]);
            }

            $stmt = $db->prepare("DELETE FROM djs WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $deleted++;
        }

        if (dedupeTableExists($db, 'videos')) {
            $stmt = $db->prepare("
                UPDATE djs d
                SET d.mixes = (SELECT COUNT(*) FROM mixes m WHERE m.dj = d.name AND m.active = 1),
                    d.videos = (SELECT COUNT(*) FROM videos v WHERE v.dj = d.name AND v.active = 1)
                WHERE d.id = :id
            ");
            $stmt->execute([':id' => $keeperId]);
        } else {
            $stmt = $db->prepare("
                UPDATE djs d
                SET d.mixes = (SELECT COUNT(*) FROM mixes m WHERE m.dj = d.name AND m.active = 1)
                WHERE d.id = :id
            ");
            $stmt->execute([':id' => $keeperId]);
        }

        $processed[] = [
            'normalized' => $normalizedName,
            'keeper' => $keeper,
            'duplicate_ids' => $duplicateIds,
        ];
    }

    dedupeAddUniqueNameIndex($db);

    return ['deleted' => $deleted, 'groups' => $processed, 'backup' => $backupTable];
}

$groups = dedupeBuildGroups($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['confirm'] ?? '') !== 'ELIMINAR DUPLICADOS') {
            throw new Exception('Escribe exactamente ELIMINAR DUPLICADOS para confirmar.');
        }

        $result = dedupeApply($db);
        $message = 'Limpieza completada. DJs duplicados eliminados: ' . (int)$result['deleted'];
        if (!empty($result['backup'])) {
            $message .= '. Respaldo creado: ' . $result['backup'];
        }

        $details = $result['groups'];
        $groups = dedupeBuildGroups($db);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Limpiar DJs Duplicados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-950 text-neutral-100 min-h-screen">
    <main class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Limpiar DJs duplicados</h1>
                <p class="text-sm text-neutral-400">Une registros repetidos por nombre normalizado y conserva el DJ con mas datos.</p>
            </div>
            <a href="../dashboard.php" class="px-3 py-2 rounded bg-neutral-800 hover:bg-neutral-700 text-sm">Volver</a>
        </div>

        <?php if ($message): ?>
            <div class="mb-4 rounded border border-green-700 bg-green-950 px-4 py-3 text-green-100"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 rounded border border-red-700 bg-red-950 px-4 py-3 text-red-100"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="rounded border border-neutral-800 bg-neutral-900 p-4 mb-6">
            <h2 class="font-semibold mb-2">Vista previa</h2>
            <?php if (!$groups): ?>
                <p class="text-green-300">No se encontraron DJs duplicados.</p>
            <?php else: ?>
                <p class="text-sm text-neutral-400 mb-4">Grupos encontrados: <?php echo count($groups); ?>. Revisa antes de ejecutar.</p>
                <div class="space-y-4">
                    <?php foreach ($groups as $normalizedName => $items): ?>
                        <?php $keeper = dedupeChooseKeeper($db, $items); ?>
                        <div class="rounded border border-neutral-800 overflow-hidden">
                            <div class="bg-neutral-800 px-3 py-2 text-sm">
                                Grupo: <strong><?php echo htmlspecialchars($normalizedName); ?></strong>
                                <span class="text-green-300 ml-2">Se conserva ID <?php echo (int)$keeper['id']; ?> - <?php echo htmlspecialchars($keeper['name']); ?></span>
                            </div>
                            <table class="w-full text-sm">
                                <thead class="text-neutral-400">
                                    <tr>
                                        <th class="text-left p-2">ID</th>
                                        <th class="text-left p-2">Nombre</th>
                                        <th class="text-left p-2">Genero</th>
                                        <th class="text-left p-2">Ciudad</th>
                                        <th class="text-left p-2">Activo</th>
                                        <th class="text-left p-2">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $dj): ?>
                                        <tr class="border-t border-neutral-800">
                                            <td class="p-2"><?php echo (int)$dj['id']; ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($dj['name']); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($dj['genre'] ?? ''); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($dj['city'] ?? ''); ?></td>
                                            <td class="p-2"><?php echo !empty($dj['active']) ? 'Si' : 'No'; ?></td>
                                            <td class="p-2 <?php echo (int)$dj['id'] === (int)$keeper['id'] ? 'text-green-300' : 'text-red-300'; ?>">
                                                <?php echo (int)$dj['id'] === (int)$keeper['id'] ? 'Conservar' : 'Unir y eliminar'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($groups): ?>
            <form method="post" class="rounded border border-red-900 bg-red-950/40 p-4">
                <h2 class="font-semibold mb-2">Ejecutar limpieza</h2>
                <p class="text-sm text-neutral-300 mb-3">
                    Esto crea una tabla de respaldo, actualiza users/site_visits/dj_payments/mixes/videos hacia el DJ conservado y elimina las filas duplicadas.
                </p>
                <label class="block text-sm mb-2" for="confirm">Escribe <strong>ELIMINAR DUPLICADOS</strong></label>
                <input id="confirm" name="confirm" class="w-full max-w-sm rounded bg-neutral-950 border border-neutral-700 px-3 py-2 mb-3" autocomplete="off">
                <button class="px-4 py-2 rounded bg-red-700 hover:bg-red-600 font-semibold">Ejecutar limpieza</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
