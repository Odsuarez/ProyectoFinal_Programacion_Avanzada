<?php
declare(strict_types=1);
$configuracion = [
    'driver' => 'mysql',
    'host' => getenv('AUTH_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('AUTH_DB_PORT') ?: '3306',
    'database' => getenv('AUTH_DB_DATABASE') ?: 'ms-auth',
    'username' => getenv('AUTH_DB_USERNAME') ?: 'root',
    'password' => getenv('AUTH_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION TRANSACTION READ ONLY'],
];
if (is_file(__DIR__ . '/auth.local.php')) {
    $configuracion = array_replace($configuracion, require __DIR__ . '/auth.local.php');
}
return $configuracion;

