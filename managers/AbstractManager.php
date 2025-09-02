<?php
abstract class AbstractManager
{
    protected PDO $db;

    public function __construct()
    {
        $host    = $_ENV['DB_HOST'];
        $port    = $_ENV['DB_PORT'] ?? '3306';
        $dbName  = $_ENV['DB_NAME'];
        $charset = $_ENV['DB_CHARSET'];
        $user    = $_ENV['DB_USER'];
        $pass    = $_ENV['DB_PASSWORD'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        $this->db = new PDO($dsn, $user, $pass);
    }
}