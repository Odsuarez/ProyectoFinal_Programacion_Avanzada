<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Http\Entrada;
use App\Http\Json;
use App\Services\MesaService;
use App\Services\ReservaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MesaController
{
    private MesaService $servicio;
    public function __construct() { $this->servicio = new MesaService(); }

    public function listar(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::responder($response, ['mesas' => $this->servicio->listar()]);
    }

    public function buscar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::responder($response, ['mesa' => $this->servicio->buscar(Entrada::id($args['id']))]);
    }

    public function crear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $mesa = $this->servicio->crear(Entrada::cuerpo($request));
        return Json::responder($response, ['mensaje' => 'Mesa creada.', 'mesa' => $mesa], 201);
    }

    public function editar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mesa = $this->servicio->editar(Entrada::id($args['id']), Entrada::cuerpo($request));
        return Json::responder($response, ['mensaje' => 'Mesa actualizada.', 'mesa' => $mesa]);
    }

    public function estado(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $datos = Entrada::cuerpo($request);
        if (array_keys($datos) !== ['estado'] || !is_string($datos['estado'])) {
            throw new \DomainException('Envía solamente el campo estado.', 422);
        }
        $mesa = $this->servicio->cambiarEstado(Entrada::id($args['id']), $datos['estado']);
        return Json::responder($response, ['mensaje' => 'Estado de mesa actualizado.', 'mesa' => $mesa]);
    }

    public function disponibles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filtros = $request->getQueryParams();
        $filtros['cantidad_personas'] = Entrada::id($filtros['cantidad_personas'] ?? null);
        return Json::responder($response, ['mesas' => (new ReservaService())->disponibles($filtros)]);
    }
}

