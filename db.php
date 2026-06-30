<?php

declare(strict_types=1);

function createPdo(): PDO
{
    $config = require __DIR__ . '/env.php';
    $db = $config['db'] ?? [];

    $host = $db['host'] ?? '127.0.0.1';
    $port = $db['port'] ?? '3306';
    $database = $db['database'] ?? 'ollama_chat';
    $username = $db['username'] ?? 'root';
    $password = $db['password'] ?? '';

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
