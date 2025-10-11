<?php
class Database {
    private $host = "localhost";
    private $db_name = "panda_truck_dashboard";
    private $username = "panda_user";
    private $password = "PandaSecure2024!";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Para XAMPP, podemos mostrar el error temporalmente
            error_log("Error de conexión: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}
?>