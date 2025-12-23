<?php
class Database {
    private $host = "localhost";
    private $db_name = "restaurant_db";
    private $username = "root";
    private $password = "";

    public function getHost() { return $this->host; }
    public function getDbName() { return $this->db_name; }
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql: host=" . $this->host .  ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO:: ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        return $this->conn;
    }
}
?>