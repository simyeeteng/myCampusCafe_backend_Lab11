<?php
class DB {
    private string $host;
    private string $user;
    private string $password;
    private string $dbname;
    
    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->user = getenv('DB_USER') ?: 'postgres';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->dbname = getenv('DB_NAME') ?: 'mycampus_cafe';
    }
    
    public function connect(): PDO {
        $dsn = "pgsql:host={$this->host};dbname={$this->dbname}";
        return new PDO($dsn, $this->user, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}
?>