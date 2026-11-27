<?php

use App\Controllers\AuthController;

use App\Middleware\Autenticacion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $controlador = new AuthController();
    $autenticacion = new Autenticacion();

    $app->post('/login', [$controlador, 'login']);
    $app->get('/sesion', [$controlador, 'sesion'])->add($autenticacion);
    $app->post('/logout', [$controlador, 'logout'])->add($autenticacion);

    // Lista explícita: no se sirven archivos PHP, configuración ni rutas arbitrarias.
    $carpetaFront = dirname(__DIR__, 4) . '/front';
    $archivos = [
        '/' => ['index.html', 'text/html'],
        '/inicio' => ['inicio.html', 'text/html'],
        '/css/estilos.css' => ['css/estilos.css', 'text/css'],
        '/js/login.js' => ['js/login.js', 'application/javascript'],
        '/js/inicio.js' => ['js/inicio.js', 'application/javascript'],
    ];
    foreach ($archivos as $ruta => [$archivo, $tipo]) {
        $app->get($ruta, function (ServerRequestInterface $request, ResponseInterface $response) use ($carpetaFront, $archivo, $tipo): ResponseInterface {
            $contenido = file_get_contents($carpetaFront . '/' . $archivo);
            if ($contenido === false) {
                throw new RuntimeException('No se encontró un archivo del frontend.');
            }
            $response->getBody()->write($contenido);
            return $response->withHeader('Content-Type', $tipo . '; charset=utf-8')
                ->withHeader('Cache-Control', 'no-store')
                ->withHeader('X-Content-Type-Options', 'nosniff');
        });
    }
};

