<?php
class DB {
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $dbname;
    
    public function __construct() {
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->port     = getenv('DB_PORT')     ?: '3306';
        $this->user     = getenv('DB_USER')     ?: 'root';
        $this->password = getenv('DB_PASS')     ?: '';
        $this->dbname   = getenv('DB_NAME')     ?: 'railway';
    }
    
    public function connect(): PDO {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
        return new PDO($dsn, $this->user, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}
?>