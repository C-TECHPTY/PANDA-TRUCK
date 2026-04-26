<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

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

function djProDate($value) {
    return $value ? date('Y-m-d', strtotime($value)) : '-';
}

function daysRemaining($endDate) {
    if (!$endDate) {
        return null;
    }
    $today = new DateTime('today');
    $end = new DateTime(date('Y-m-d', strtotime($endDate)));
    return (int)$today->diff($end)->format('%r%a');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $djId = isset($_POST['dj_id']) ? (int)$_POST['dj_id'] : 0;

    try {
        if ($djId <= 0) {
            throw new Exception('DJ invalido.');
        }

        if ($action === 'activate') {
            $stmt = $db->prepare("UPDATE djs SET plan = 'pro', subscription_status = 'active', subscription_start = NOW(), subscription_end = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = :id");
            $stmt->execute([':id' => $djId]);
            $message = 'DJ PRO activado por 30 dias.';
        } elseif ($action === 'extend') {
            $stmt = $db->prepare("UPDATE djs
                SET plan = 'pro',
                    subscription_status = 'active',
                    subscription_start = COALESCE(subscription_start, NOW()),
                    subscription_end = CASE
                        WHEN subscription_end IS NOT NULL AND subscription_end > NOW()
                            THEN DATE_ADD(subscription_end, INTERVAL 30 DAY)
                        ELSE DATE_ADD(NOW(), INTERVAL 30 DAY)
                    END
                WHERE id = :id");
            $stmt->execute([':id' => $djId]);
            $message = 'Suscripcion extendida 30 dias.';
        } elseif ($action === 'free') {
            $stmt = $db->prepare("UPDATE djs SET plan = 'free', subscription_status = 'cancelled', is_featured = 0, priority = 0 WHERE id = :id");
            $stmt->execute([':id' => $djId]);
            $message = 'DJ marcado como FREE.';
        } elseif ($action === 'founder') {
            $stmt = $db->prepare("UPDATE djs
                                  SET plan = 'founder',
                                      subscription_status = 'active',
                                      subscription_start = COALESCE(subscription_start, NOW()),
                                      subscription_end = NULL
                                  WHERE id = :id");
            $stmt->execute([':id' => $djId]);
            $message = 'DJ marcado como Fundador.';
        } elseif ($action === 'pause') {
            $stmt = $db->prepare("UPDATE djs SET subscription_status = 'cancelled', is_featured = 0 WHERE id = :id");
            $stmt->execute([':id' => $djId]);
            $message = 'Suscripcion pausada.';
        } elseif ($action === 'featured') {
            $featured = isset($_POST['is_featured']) ? 1 : 0;
            $priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
            $stmt = $db->prepare("UPDATE djs SET is_featured = :featured, priority = :priority WHERE id = :id");
            $stmt->execute([':featured' => $featured, ':priority' => $priority, ':id' => $djId]);
            $message = 'Visibilidad actualizada.';
        } elseif ($action === 'payment') {
            $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 10.00;
            $reference = trim($_POST['reference_number'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $stmt = $db->prepare("INSERT INTO dj_payments (dj_id, amount, payment_method, reference_number, notes, payment_date, created_by)
                                  VALUES (:dj_id, :amount, 'Yappy', :reference, :notes, NOW(), :created_by)");
            $stmt->execute([
                ':dj_id' => $djId,
                ':amount' => $amount,
                ':reference' => $reference,
                ':notes' => $notes,
                ':created_by' => $_SESSION['user_id'] ?? null,
            ]);
            $stmt = $db->prepare("UPDATE djs
                SET plan = 'pro',
                    subscription_status = 'active',
                    subscription_start = COALESCE(subscription_start, NOW()),
                    subscription_end = CASE
                        WHEN subscription_end IS NOT NULL AND subscription_end > NOW()
                            THEN DATE_ADD(subscription_end, INTERVAL 30 DAY)
                        ELSE DATE_ADD(NOW(), INTERVAL 30 DAY)
                    END
                WHERE id = :id");
            $stmt->execute([':id' => $djId]);
            $message = 'Pago Yappy registrado y suscripcion extendida.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$summary = $db->query("SELECT
    COUNT(*) AS total_djs,
    SUM(plan = 'pro' AND subscription_status = 'active' AND subscription_end >= NOW()) AS pro_active,
    SUM(plan = 'founder' AND subscription_status = 'active') AS founders,
    SUM(subscription_status = 'expired' OR (plan = 'pro' AND subscription_end < NOW())) AS expired,
    COALESCE((SELECT SUM(amount) FROM dj_payments), 0) AS yappy_total,
    COALESCE((SELECT COUNT(*) FROM site_visits), 0) AS total_visits
    FROM djs WHERE active = 1")->fetch(PDO::FETCH_ASSOC);

$djs = $db->query("SELECT
    d.*,
    (SELECT COUNT(*) FROM mixes m WHERE m.dj = d.name AND m.active = 1) AS mix_count,
    (SELECT COUNT(*) FROM site_visits sv WHERE sv.dj_id = d.id) AS profile_visits,
    (SELECT COALESCE(SUM(s.plays), 0) FROM mixes m LEFT JOIN statistics s ON s.item_id = m.id AND s.item_type = 'mix' WHERE m.dj = d.name AND m.active = 1) AS total_plays
    FROM djs d
    WHERE d.active = 1
    ORDER BY d.plan = 'pro' DESC, d.subscription_end ASC, d.name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DJ PRO - Panda Truck</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-neutral-950 text-white">
    <main class="max-w-7xl mx-auto p-4 md:p-6">
        <div class="flex flex-col md:flex-row justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Gestion DJ PRO</h1>
                <p class="text-neutral-400 text-sm">Plan unico: $10 mensual por Yappy.</p>
            </div>
            <div class="flex gap-2">
                <a href="../dashboard.php" class="px-4 py-2 rounded bg-neutral-800 hover:bg-neutral-700">Dashboard</a>
                <a href="reports/generate_partner_report.php" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700">Reporte PDF</a>
            </div>
        </div>

        <?php if ($message): ?><div class="mb-4 p-3 rounded bg-green-700/30 text-green-200"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mb-4 p-3 rounded bg-red-700/30 text-red-200"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">DJs</p><p class="text-2xl font-bold"><?php echo (int)$summary['total_djs']; ?></p></div>
            <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">PRO activos</p><p class="text-2xl font-bold text-green-400"><?php echo (int)$summary['pro_active']; ?></p></div>
            <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">Fundadores</p><p class="text-2xl font-bold text-amber-400"><?php echo (int)$summary['founders']; ?></p></div>
            <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">Yappy</p><p class="text-2xl font-bold">$<?php echo number_format((float)$summary['yappy_total'], 2); ?></p></div>
            <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">Visitas</p><p class="text-2xl font-bold"><?php echo number_format((int)$summary['total_visits']); ?></p></div>
        </div>

        <div class="overflow-x-auto bg-neutral-900 rounded">
            <table class="w-full text-sm">
                <thead class="bg-neutral-800 text-neutral-300">
                    <tr>
                        <th class="p-3 text-left">DJ</th>
                        <th class="p-3">Plan</th>
                        <th class="p-3">Estado</th>
                        <th class="p-3">Inicio</th>
                        <th class="p-3">Vence</th>
                        <th class="p-3">Dias</th>
                        <th class="p-3">Mixes</th>
                        <th class="p-3">Visitas</th>
                        <th class="p-3">Plays</th>
                        <th class="p-3">Destacado</th>
                        <th class="p-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($djs as $dj): $days = daysRemaining($dj['subscription_end']); ?>
                    <tr class="border-t border-neutral-800 align-top">
                        <td class="p-3">
                            <div class="font-semibold"><?php echo htmlspecialchars($dj['name']); ?></div>
                            <div class="text-xs text-neutral-500"><?php echo htmlspecialchars($dj['email'] ?? ''); ?></div>
                            <a href="../dj.php?slug=<?php echo urlencode($dj['slug'] ?: $dj['id']); ?>" target="_blank" class="text-xs text-red-400">Ver perfil</a>
                        </td>
                        <td class="p-3 text-center"><?php echo strtoupper(htmlspecialchars($dj['plan'] ?? 'free')); ?></td>
                        <td class="p-3 text-center"><?php echo htmlspecialchars($dj['subscription_status'] ?? 'pending'); ?></td>
                        <td class="p-3 text-center"><?php echo djProDate($dj['subscription_start']); ?></td>
                        <td class="p-3 text-center"><?php echo djProDate($dj['subscription_end']); ?></td>
                        <td class="p-3 text-center <?php echo $days !== null && $days < 0 ? 'text-red-400' : 'text-green-400'; ?>"><?php echo $days === null ? '-' : $days; ?></td>
                        <td class="p-3 text-center"><?php echo (int)$dj['mix_count']; ?></td>
                        <td class="p-3 text-center"><?php echo number_format((int)$dj['profile_visits']); ?></td>
                        <td class="p-3 text-center"><?php echo number_format((int)$dj['total_plays']); ?></td>
                        <td class="p-3 text-center">
                            <form method="post" class="flex flex-col gap-1">
                                <input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>">
                                <input type="hidden" name="action" value="featured">
                                <label class="text-xs"><input type="checkbox" name="is_featured" <?php echo !empty($dj['is_featured']) ? 'checked' : ''; ?>> Si</label>
                                <input type="number" name="priority" value="<?php echo (int)($dj['priority'] ?? 0); ?>" class="w-16 bg-neutral-800 rounded p-1 text-center">
                                <button class="text-xs bg-neutral-700 rounded px-2 py-1">Guardar</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-1 mb-2">
                                <form method="post"><input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>"><input type="hidden" name="action" value="activate"><button class="px-2 py-1 rounded bg-green-700 text-xs">Activar PRO</button></form>
                                <form method="post"><input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>"><input type="hidden" name="action" value="founder"><button class="px-2 py-1 rounded bg-amber-700 text-xs">Fundador</button></form>
                                <form method="post"><input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>"><input type="hidden" name="action" value="extend"><button class="px-2 py-1 rounded bg-blue-700 text-xs">+30 dias</button></form>
                                <form method="post"><input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>"><input type="hidden" name="action" value="pause"><button class="px-2 py-1 rounded bg-yellow-700 text-xs">Pausar</button></form>
                                <form method="post"><input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>"><input type="hidden" name="action" value="free"><button class="px-2 py-1 rounded bg-neutral-700 text-xs">FREE</button></form>
                            </div>
                            <form method="post" class="grid grid-cols-2 gap-1">
                                <input type="hidden" name="dj_id" value="<?php echo (int)$dj['id']; ?>">
                                <input type="hidden" name="action" value="payment">
                                <input type="number" step="0.01" name="amount" value="10.00" class="bg-neutral-800 rounded p-1">
                                <input type="text" name="reference_number" placeholder="Ref. Yappy" class="bg-neutral-800 rounded p-1">
                                <input type="text" name="notes" placeholder="Notas" class="col-span-2 bg-neutral-800 rounded p-1">
                                <button class="col-span-2 px-2 py-1 rounded bg-red-700 text-xs">Registrar pago Yappy</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
