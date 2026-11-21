<?php
declare(strict_types=1);

use App\Http\Json;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errores = $app->addErrorMiddleware(false, true, true);
$errores->setDefaultErrorHandler(function (
    ServerRequestInterface $request,
    Throwable $error,
    bool $mostrarDetalles,
    bool $registrarErrores,
    bool $registrarDetalles
) use ($app) {
    $estado = $error instanceof HttpException ? $error->getCode() : 500;
    if ($estado < 400 || $estado > 599) {
        $estado = 500;
    }
    if ($estado === 500) {
        error_log((string) $error);
    }
    $mensaje = $estado === 500
        ? 'No se pudo procesar la solicitud. Revisa la conexión y la configuración del servicio.'
        : ($estado === 400 ? 'La solicitud no tiene un formato válido.' : 'La ruta o el método solicitado no está disponible.');
    return Json::responder($app->getResponseFactory()->createResponse(), ['mensaje' => $mensaje], $estado);
});

(require __DIR__ . '/../app/Routes/endpoints.php')($app);
$app->run();

