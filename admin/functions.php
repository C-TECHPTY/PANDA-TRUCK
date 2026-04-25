<?php
// admin/functions.php
require_once '../includes/config.php';

function getAllUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT id, username, email, role, dj_id, last_login, active, created_at FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function createUser($username, $password, $email, $role, $dj_id = null) {
    $db = getDB();
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, email, role, dj_id) VALUES (:username, :password, :email, :role, :dj_id)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':dj_id', $dj_id);
    
    return $stmt->execute();
}

function updateUser($id, $data) {
    $db = getDB();
    $fields = [];
    $params = [':id' => $id];
    
    if (isset($data['username'])) {
        $fields[] = "username = :username";
        $params[':username'] = $data['username'];
    }
    if (isset($data['email'])) {
        $fields[] = "email = :email";
        $params[':email'] = $data['email'];
    }
    if (isset($data['role'])) {
        $fields[] = "role = :role";
        $params[':role'] = $data['role'];
    }
    if (isset($data['password']) && !empty($data['password'])) {
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $fields[] = "password = :password";
        $params[':password'] = $hashed;
    }
    if (isset($data['active'])) {
        $fields[] = "active = :active";
        $params[':active'] = $data['active'];
    }
    
    if (empty($fields)) return false;
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function deleteUser($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role != 'superadmin'");
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

function getSystemLogs($limit = 100) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function clearCache() {
    $db = getDB();
    $db->exec("DELETE FROM sync_cache WHERE expires_at < NOW()");
    return true;
}

function backupDatabase() {
    $db = getDB();
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch()) {
        $tables[] = array_values($row)[0];
    }
    
    $backup = "-- Backup generado el " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll();
        
        if (empty($rows)) continue;
        
        $backup .= "-- Tabla: $table\n";
        $backup .= "TRUNCATE TABLE `$table`;\n";
        
        foreach ($rows as $row) {
            $values = array_map(function($val) use ($db) {
                return $db->quote($val);
            }, array_values($row));
            $backup .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
        }
        $backup .= "\n";
    }
    
    $filename = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($filename, $backup);
    
    return $filename;
}
?>