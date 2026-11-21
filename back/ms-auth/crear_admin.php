<?php
declare(strict_types=1);

use App\Models\Usuario;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    fwrite(STDERR, "Primero ejecuta composer install.\n");
    exit(1);
}

try {
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/app/Config/database.php';
    if (Usuario::where('usuario', 'admin')->orWhere('correo', 'admin@restaurante.com')->exists()) {
        echo "El administrador ya existe. No se cambiaron sus datos ni su clave.\n";
        exit(0);
    }
    $usuario = new Usuario();
    $usuario->nombre = 'Administrador General';
    $usuario->correo = 'admin@restaurante.com';
    $usuario->usuario = 'admin';
    $usuario->contrasena = password_hash('admin123', PASSWORD_DEFAULT);
    $usuario->rol = 'administrador';
    $usuario->estado = 'activo';
    $usuario->sesion_activa = false;
    $usuario->save();
    echo "Usuario de prueba creado: admin / admin123.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo crear el administrador. Comprueba la conexion y la tabla usuarios.\n");
    exit(1);
}
