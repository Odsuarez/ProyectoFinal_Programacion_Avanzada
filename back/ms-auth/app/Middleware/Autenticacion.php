<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Http\Json;
use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class Autenticacion
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cabecera = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer ([a-f0-9]{64})$/D', $cabecera, $coincidencias)) {
            return Json::responder(new Response(), ['mensaje' => 'Debes iniciar sesión.'], 401);
        }
        $usuario = Usuario::where('token', hash('sha256', $coincidencias[1]))
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();
        if (!$usuario) {
            return Json::responder(new Response(), ['mensaje' => 'La sesión ya no está activa.'], 401);
        }
        return $handler->handle($request->withAttribute('usuario', $usuario));
    }
}

