<?php
declare(strict_types=1);
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $app->get('/health', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $response->getBody()->write(json_encode([
            'servicio' => 'ms-reservas',
            'estado' => 'disponible',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    });
    // Próxima etapa: controladores y rutas de mesas con autenticación.
};

