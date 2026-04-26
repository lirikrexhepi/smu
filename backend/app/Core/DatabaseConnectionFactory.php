<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class DatabaseConnectionFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(): PDO
    {
        $database = $this->config->get('database.database');
        $host = $this->config->get('database.host');
        $port = $this->config->get('database.port');
        $charset = $this->config->get('database.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        return new PDO($dsn, (string) $this->config->get('database.username'), (string) $this->config->get('database.password'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
