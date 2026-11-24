<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Http\Json;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class Autenticacion
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!preg_match('/^Bearer ([a-f0-9]{64})$/D', $request->getHeaderLine('Authorization'), $coincidencias)) {
            return Json::responder(new Response(), ['mensaje' => 'Debes iniciar sesión.'], 401);
        }
        // Solo SELECT. Usa la misma huella SHA-256 que genera ms-auth.
        $usuario = Capsule::connection('auth')->table('usuarios')
            ->select('id', 'nombre', 'rol')
            ->where('token', hash('sha256', $coincidencias[1]))
            ->where('sesion_activa', true)
            ->where('estado', 'activo')->first();
        if (!$usuario) {
            return Json::responder(new Response(), ['mensaje' => 'La sesión ya no está activa.'], 401);
        }
        return $handler->handle($request->withAttribute('usuario', $usuario));
    }
}

