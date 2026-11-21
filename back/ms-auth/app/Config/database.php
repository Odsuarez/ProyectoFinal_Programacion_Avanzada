<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$configuracion = [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'ms-auth',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];

$archivoLocal = __DIR__ . '/database.local.php';
if (is_file($archivoLocal)) {
    $configuracion = array_replace($configuracion, require $archivoLocal);
}

$capsule = new Capsule();
$capsule->addConnection($configuracion);
$capsule->setAsGlobal();
$capsule->bootEloquent();

return $capsule;

