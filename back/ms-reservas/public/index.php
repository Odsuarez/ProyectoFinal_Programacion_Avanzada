<?php
declare(strict_types=1);
use Slim\Factory\AppFactory;
use Slim\Exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errores = $app->addErrorMiddleware(false, true, true);
$errores->setDefaultErrorHandler(function (
    ServerRequestInterface $request, Throwable $error,
    bool $mostrar, bool $registrar, bool $detalles
) use ($app) {
    $estado = $error instanceof HttpException ? $error->getCode() : 500;
    if ($estado < 400 || $estado > 599) $estado = 500;
    if ($estado === 500) error_log((string) $error);
    $response = $app->getResponseFactory()->createResponse($estado);
    $response->getBody()->write(json_encode([
        'mensaje' => $estado === 500 ? 'No se pudo procesar la solicitud.' : 'Ruta o método no disponible.',
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});
(require __DIR__ . '/../app/Routes/endpoints.php')($app);
$app->run();

