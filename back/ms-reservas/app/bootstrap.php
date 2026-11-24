<?php
declare(strict_types=1);
use App\Http\Json;
use App\Middleware\Cors;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;

$configuracion = require __DIR__ . '/Config/aplicacion.php';
date_default_timezone_set($configuracion['timezone']);
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errores = $app->addErrorMiddleware(false, true, true);
$errores->setDefaultErrorHandler(function (
    ServerRequestInterface $request, Throwable $error,
    bool $mostrar, bool $registrar, bool $detalles
) use ($app) {
    $estado = $error instanceof HttpException || $error instanceof DomainException ? $error->getCode() : 500;
    if ($estado < 400 || $estado > 599) $estado = 500;
    if ($estado === 500) error_log((string) $error);
    $mensaje = $error instanceof DomainException ? $error->getMessage() : (
        $estado === 500 ? 'No se pudo procesar la solicitud. Comprueba la conexión del servicio.' : (
            $estado === 400 ? 'El JSON no es válido.' : 'Ruta o método no disponible.'
        )
    );
    return Json::responder($app->getResponseFactory()->createResponse(), ['mensaje' => $mensaje], $estado);
});
$app->add(new Cors());
(require __DIR__ . '/Routes/endpoints.php')($app);
return $app;

