<?php
class DB {
    private string $host = getenv('DB_HOST') ?: 'localhost';
    private string $user = getenv('DB_USER') ?: 'postgres';
    private string $password = getenv('DB_PASSWORD') ?: '';
    private string $dbname = getenv('DB_NAME') ?: 'mycampus_cafe';
    
    public function connect(): PDO {
        $dsn = "pgsql:host={$this->host};dbname={$this->dbname}";
        return new PDO($dsn, $this->user, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}
?>