<?php
declare(strict_types=1);
use App\Controllers\MesaController;
use App\Controllers\ReservaController;
use App\Http\Json;
use App\Middleware\Autenticacion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/health', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return Json::responder($response, ['servicio' => 'ms-reservas', 'estado' => 'disponible']);
    });
    $app->options('/{ruta:.*}', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $response->withStatus(204);
    });
    $app->group('', function (RouteCollectorProxy $rutas): void {
        $mesas = new MesaController();
        $reservas = new ReservaController();
        $rutas->get('/mesas', [$mesas, 'listar']);
        $rutas->get('/mesas/disponibles', [$mesas, 'disponibles']);
        $rutas->get('/mesas/{id:[0-9]+}', [$mesas, 'buscar']);
        $rutas->post('/mesas', [$mesas, 'crear']);
        $rutas->map(['PUT', 'PATCH'], '/mesas/{id:[0-9]+}', [$mesas, 'editar']);
        $rutas->patch('/mesas/{id:[0-9]+}/estado', [$mesas, 'estado']);
        $rutas->get('/reservas', [$reservas, 'listar']);
        $rutas->get('/reservas/{id:[0-9]+}', [$reservas, 'buscar']);
        $rutas->post('/reservas', [$reservas, 'crear']);
        $rutas->map(['PUT', 'PATCH'], '/reservas/{id:[0-9]+}', [$reservas, 'editar']);
        $rutas->patch('/reservas/{id:[0-9]+}/estado', [$reservas, 'estado']);
        $rutas->post('/reservas/{id:[0-9]+}/cancelar', [$reservas, 'cancelar']);
    })->add(new Autenticacion());
};

