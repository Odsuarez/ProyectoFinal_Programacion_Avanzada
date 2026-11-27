<?php

namespace App\Controllers;

use App\Http\Json;
use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $datos = $request->getParsedBody();
        if (!is_array($datos)
            || !is_string($datos['identificador'] ?? null)
            || !is_string($datos['contrasena'] ?? null)) {
            return Json::responder($response, ['mensaje' => 'Ingresa usuario o correo y contraseña.'], 422);
        }

        $identificador = trim($datos['identificador']);
        $contrasena = $datos['contrasena'];
        if ($identificador === '' || trim($contrasena) === ''
            || strlen($identificador) > 150 || strlen($contrasena) > 4096) {
            return Json::responder($response, ['mensaje' => 'Revisa los campos del formulario.'], 422);
        }

        $usuario = Usuario::where(function ($consulta) use ($identificador): void {
            $consulta->where('usuario', $identificador)->orWhere('correo', $identificador);
        })->where('estado', 'activo')->first();

        $guardada = $usuario ? (string) $usuario->contrasena : '';
        $esHash = password_get_info($guardada)['algoName'] !== 'unknown';
        $valida = $esHash
            ? password_verify($contrasena, $guardada)
            : ($guardada !== '' && hash_equals($guardada, $contrasena));

        if (!$usuario || !$valida) {
            return Json::responder($response, ['mensaje' => 'Usuario o contraseña incorrectos.'], 401);
        }

        // Los datos iniciales del Word usan texto plano: se convierten al ingresar.
        if (!$esHash || password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
            $usuario->contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        }
        $token = bin2hex(random_bytes(32));
        // Solo se guarda la huella del token; el token original se entrega al navegador.
        $usuario->token = hash('sha256', $token);
        $usuario->sesion_activa = true;
        $usuario->save();

        return Json::responder($response, [
            'mensaje' => 'Sesión iniciada.',
            'token' => $token,
            'usuario' => $this->datosUsuario($usuario),
        ]);
    }

    public function sesion(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::responder($response, [
            'usuario' => $this->datosUsuario($request->getAttribute('usuario')),
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $usuario = $request->getAttribute('usuario');
        // No invalida una sesión nueva si hubo otro login durante esta solicitud.
        Usuario::where('id', $usuario->id)->where('token', $usuario->token)->update([
            'token' => null,
            'sesion_activa' => false,
        ]);
        return Json::responder($response, ['mensaje' => 'Sesión cerrada.']);
    }

    private function datosUsuario(Usuario $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'usuario' => $usuario->usuario,
            'correo' => $usuario->correo,
            'rol' => $usuario->rol,
        ];
    }
}

