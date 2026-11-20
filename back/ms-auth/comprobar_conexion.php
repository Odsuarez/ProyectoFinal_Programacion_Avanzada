<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Primero ejecuta composer install en back/ms-auth.\n");
    exit(1);
}

try {
    require $autoload;
    $capsule = require __DIR__ . '/app/Config/database.php';
    $capsule->getConnection()->getPdo();
    $tablaExiste = $capsule->getConnection()->getSchemaBuilder()->hasTable('usuarios');
    if (!$tablaExiste) {
        fwrite(STDERR, "Hay conexion, pero falta la tabla usuarios. Importa database/estructura.sql.\n");
        exit(1);
    }
    echo "Conexion correcta. La tabla usuarios esta disponible.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo comprobar la conexion. Revisa MySQL, la base de datos y app/Config/database.local.php.\n");
    exit(1);
}

