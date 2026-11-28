<?php

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
        $token = $this->leerToken($request->getHeaderLine('Authorization'));
        if ($token === null) {
            return Json::responder(new Response(), ['mensaje' => 'Debes iniciar sesión.'], 401);
        }
        // Solo SELECT. Usa la misma huella SHA-256 que genera ms-auth.
        $usuario = Capsule::connection('auth')->table('usuarios')
            ->select('id', 'nombre', 'rol')
            ->where('token', hash('sha256', $token))
            ->where('sesion_activa', true)
            ->where('estado', 'activo')->first();
        if (!$usuario) {
            return Json::responder(new Response(), ['mensaje' => 'La sesión ya no está activa.'], 401);
        }
        return $handler->handle($request->withAttribute('usuario', $usuario));
    }

    private function leerToken(string $cabecera): ?string
    {
        if (strlen($cabecera) !== 71 || substr($cabecera, 0, 7) !== 'Bearer ') {
            return null;
        }

        $token = substr($cabecera, 7);
        for ($i = 0; $i < 64; $i++) {
            if (strpos('0123456789abcdef', $token[$i]) === false) {
                return null;
            }
        }
        return $token;
    }
}

