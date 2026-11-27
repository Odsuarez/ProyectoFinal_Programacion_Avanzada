<?php

namespace App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


final class Cors
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $origen = $request->getHeaderLine('Origin');
        if (in_array($origen, ['http://127.0.0.1:8000', 'http://localhost:8000'], true)) {
            return $response->withHeader('Access-Control-Allow-Origin', $origen)
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, OPTIONS');
        }
        return $response;
    }
}

