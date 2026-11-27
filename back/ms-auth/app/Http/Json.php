<?php

namespace App\Http;

use Psr\Http\Message\ResponseInterface;

final class Json
{
    public static function responder(ResponseInterface $response, array $datos, int $estado = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withStatus($estado)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
    }
}

