<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Http\Entrada;
use App\Http\Json;
use App\Services\ReservaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ReservaController
{
    private ReservaService $servicio;
    public function __construct() { $this->servicio = new ReservaService(); }

    public function listar(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::responder($response, ['reservas' => $this->servicio->listar($request->getQueryParams())]);
    }

    public function buscar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::responder($response, ['reserva' => $this->servicio->buscar(Entrada::id($args['id']))]);
    }

    public function crear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::responder($response, [
            'mensaje' => 'Reserva creada.',
            'reserva' => $this->servicio->crear(Entrada::cuerpo($request)),
        ], 201);
    }

    public function editar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $reserva = $this->servicio->editar(Entrada::id($args['id']), Entrada::cuerpo($request));
        return Json::responder($response, ['mensaje' => 'Reserva actualizada.', 'reserva' => $reserva]);
    }

    public function estado(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $datos = Entrada::cuerpo($request);
        if (array_keys($datos) !== ['estado']) throw new \DomainException('Envía solamente el campo estado.', 422);
        $reserva = $this->servicio->cambiarEstado(Entrada::id($args['id']), $datos['estado']);
        return Json::responder($response, ['mensaje' => 'Estado de reserva actualizado.', 'reserva' => $reserva]);
    }

    public function cancelar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $reserva = $this->servicio->cambiarEstado(Entrada::id($args['id']), 'cancelada');
        return Json::responder($response, ['mensaje' => 'Reserva cancelada.', 'reserva' => $reserva]);
    }
}

