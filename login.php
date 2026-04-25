<?php
// login.php
session_start();
require_once 'includes/config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND active = 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } catch (PDOException $e) {
        $error = 'Error de conexión: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-neutral-950 text-white min-h-screen flex items-center justify-center">
    <div class="bg-neutral-900 rounded-2xl p-8 max-w-md w-full mx-4 border border-neutral-800">
        <div class="text-center mb-8">
            <img src="assets/img/logo.png" alt="Panda Truck" class="h-16 mx-auto mb-4" onerror="this.src='https://via.placeholder.com/64?text=P'">
            <h1 class="text-2xl font-bold">Panda Truck Reloaded</h1>
            <p class="text-neutral-400 mt-2">Panel de Administración</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 rounded-lg p-3 mb-6 text-red-400 text-center text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2">Usuario</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
                    <input type="text" name="username" required 
                           class="w-full pl-10 pr-4 py-3 rounded-xl bg-neutral-800 border border-neutral-700 focus:outline-none focus:border-primary">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Contraseña</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
                    <input type="password" name="password" required 
                           class="w-full pl-10 pr-4 py-3 rounded-xl bg-neutral-800 border border-neutral-700 focus:outline-none focus:border-primary">
                </div>
            </div>
            <button type="submit" class="w-full py-3 rounded-xl bg-primary hover:bg-primary-hover transition font-semibold">
                <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="mt-6 text-center text-xs text-neutral-500 border-t border-neutral-800 pt-4">
            <p>Acceso autorizado para administradores</p>
        </div>
    </div>
</body>
</html>