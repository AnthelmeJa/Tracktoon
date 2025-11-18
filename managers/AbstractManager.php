<?php
abstract class AbstractManager
{
    protected PDO $db;

    public function __construct()
    {
        $host    = getenv('DB_HOST')     ?: ($_ENV['DB_HOST']     ?? '127.0.0.1');
        $port    = getenv('DB_PORT')     ?: ($_ENV['DB_PORT']     ?? '3306');
        $dbName  = getenv('DB_NAME')     ?: ($_ENV['DB_NAME']     ?? 'tracktoon');
        $charset = getenv('DB_CHARSET')  ?: ($_ENV['DB_CHARSET']  ?? 'utf8mb4');
        $user    = getenv('DB_USER')     ?: ($_ENV['DB_USER']     ?? 'root');
        $pass    = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        
        $sslCaPath = getenv('DB_SSL_CA_PATH') ?: ($_ENV['DB_SSL_CA_PATH'] ?? null);

        if ($sslCaPath) {
           
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;

           
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        $this->db = new PDO($dsn, $user, $pass, $options);
    }
}