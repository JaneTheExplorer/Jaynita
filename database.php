<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'flyflex3';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("Database connection established successfully");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            // Don't expose database errors in production
            throw new Exception("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}
?>
