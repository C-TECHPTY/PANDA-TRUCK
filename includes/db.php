<?php
// includes/db.php
class Database {
    private $host = "localhost";
    private $db_name = "panda_truck_v2";
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct() {
        $localConfigFile = __DIR__ . '/config.local.php';
        $localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];

        $configValue = function ($key, $default) use ($localConfig) {
            if (array_key_exists($key, $localConfig)) {
                return $localConfig[$key];
            }

            $value = getenv($key);
            return $value !== false ? $value : $default;
        };

        $this->host = $configValue('DB_HOST', $this->host);
        $this->db_name = $configValue('DB_NAME', $this->db_name);
        $this->username = $configValue('DB_USER', $this->username);
        $this->password = $configValue('DB_PASS', $this->password);
    }

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
            die("Error de conexión: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>
