<?php
// includes/auth.php - Sistema de autenticación y roles
require_once 'config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        // No iniciar sesión aquí, ya está iniciada en config.php
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND active = 1");
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            $this->logActivity($user['id'], 'Inicio de sesión');
            
            $stmt2 = $this->db->prepare("UPDATE users SET last_login = NOW(), last_ip = :ip WHERE id = :id");
            $stmt2->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
            $stmt2->bindValue(':id', $user['id']);
            $stmt2->execute();
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'Cierre de sesión');
        }
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!in_array($_SESSION['user_role'], ['superadmin', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requieren permisos de administrador.']);
            exit;
        }
    }
    
    public function requireSuperAdmin() {
        $this->requireLogin();
        if ($_SESSION['user_role'] !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requieren permisos de Super Administrador.']);
            exit;
        }
    }
    
    public function isSuperAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'superadmin';
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && in_array($_SESSION['user_role'], ['superadmin', 'admin']);
    }
    
    public function logActivity($userId, $action, $details = null) {
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->db->prepare("INSERT INTO activity_logs (user_id, username, action, details, ip_address, user_agent) 
                                    VALUES (:user_id, :username, :action, :details, :ip, :ua)");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':details', $details);
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':ua', $ua);
        return $stmt->execute();
    }
    
    public function getSystemStats() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM users WHERE active = 1");
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM mixes WHERE active = 1");
        $total_mixes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM djs WHERE active = 1");
        $total_djs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $this->db->query("SELECT SUM(downloads) as total FROM statistics");
        $total_downloads = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()");
        $activity_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM users WHERE active = 1 GROUP BY role");
        $users_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_users' => $total_users,
            'total_mixes' => $total_mixes,
            'total_djs' => $total_djs,
            'total_downloads' => $total_downloads,
            'activity_today' => $activity_today,
            'users_by_role' => $users_by_role
        ];
    }
    
    public function getActivityLogs($limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDJsList() {
        $stmt = $this->db->query("SELECT id, name FROM djs WHERE active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUsers() {
        $stmt = $this->db->query("SELECT u.*, d.name as dj_name 
                                  FROM users u 
                                  LEFT JOIN djs d ON u.dj_id = d.id 
                                  ORDER BY u.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Inicializar autenticación
$auth = new Auth();
?>