<?php

/**
 * Creates the local development and testing databases.
 *
 * Run once on a fresh machine: php database/bootstrap-databases.php
 */
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

$databases = ['laravel', 'medstoyourdoors_testing'];

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    foreach ($databases as $database) {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "ready: {$database}\n";
    }

    echo "DATABASES_READY\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    exit(1);
}
