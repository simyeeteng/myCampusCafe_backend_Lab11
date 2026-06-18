<?php
class DB {
    private string $host = "localhost";
    private string $user = "root";
    private string $password = "";
    private string $dbname = "mycampus_cafe";

    public function connect(): PDO {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        return new PDO($dsn, $this->user, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}
?>