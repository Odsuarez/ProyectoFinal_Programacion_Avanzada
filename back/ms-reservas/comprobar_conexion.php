<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    fwrite(STDERR, "Primero ejecuta composer install.\n");
    exit(1);
}
try {
    require __DIR__ . '/vendor/autoload.php';
    $capsule = require __DIR__ . '/app/Config/database.php';
    $conexion = $capsule->getConnection();
    $conexion->getPdo();
    foreach (['mesas', 'reservas'] as $tabla) {
        if (!$conexion->getSchemaBuilder()->hasTable($tabla)) {
            fwrite(STDERR, "Falta la tabla $tabla. Revisa el SQL del Word.\n");
            exit(1);
        }
    }
    echo "Conexion correcta. Las tablas mesas y reservas estan disponibles.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo comprobar la conexion. Revisa MySQL y app/Config/database.local.php.\n");
    exit(1);
}

