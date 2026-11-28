<?php

namespace App\Services;
use App\Models\Mesa;
use App\Models\Reserva;
use DateTimeImmutable;
use DomainException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;

final class ReservaService
{
    public const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
    public const ACTIVAS = ['pendiente', 'confirmada'];

    public function listar(array $filtros): Collection
    {
        $consulta = Reserva::with('mesa');
        if (isset($filtros['fecha'])) {
            $consulta->where('fecha', $this->fecha($filtros['fecha']));
        }
        if (isset($filtros['cliente'])) {
            $cliente = $this->texto($filtros['cliente'], 'El cliente', 150);
            $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $cliente) . '%';
            $consulta->whereRaw("nombre_cliente LIKE ? ESCAPE '!'", [$patron]);
        }
        if (isset($filtros['estado'])) {
            $consulta->where('estado', $this->estado($filtros['estado']));
        }
        return $consulta->orderBy('fecha')->orderBy('hora')->orderBy('id')->get();
    }

    public function buscar(int $id): Reserva
    {
        $reserva = Reserva::with('mesa')->find($id);
        if (!$reserva) throw new DomainException('La reserva no existe.', 404);
        return $reserva;
    }

    public function crear(array $datos): Reserva
    {
        $this->campos($datos, ['nombre_cliente', 'telefono_cliente', 'cantidad_personas', 'fecha', 'hora', 'mesa_id', 'observaciones']);
        $nombre = $this->texto($datos['nombre_cliente'] ?? null, 'El nombre del cliente', 150);
        $telefono = $this->texto($datos['telefono_cliente'] ?? null, 'El teléfono', 30);
        $valores = $this->datosHorario($datos);
        return Capsule::connection()->transaction(function () use ($datos, $nombre, $telefono, $valores) {
            $mesa = $this->bloquearMesa($valores['mesa_id']);
            $this->validarAsignacion($mesa, $valores);
            $reserva = Reserva::create(array_merge($valores, [
                'nombre_cliente' => $nombre,
                'telefono_cliente' => $telefono,
                'observaciones' => $this->observaciones($datos['observaciones'] ?? null),
                'estado' => 'pendiente',
            ]));
            return $reserva->load('mesa');
        }, 3);
    }

    public function editar(int $id, array $datos): Reserva
    {
        $this->campos($datos, ['cantidad_personas', 'fecha', 'hora', 'mesa_id', 'observaciones']);
        if ($datos === []) throw new DomainException('Debes enviar al menos un campo para editar.', 422);
        return Capsule::connection()->transaction(function () use ($id, $datos) {
            [$reserva, $mesas] = $this->bloquearReserva($id, $datos['mesa_id'] ?? null);
            $this->editable($reserva);
            $base = [
                'cantidad_personas' => $reserva->cantidad_personas,
                'fecha' => $reserva->fecha,
                'hora' => $reserva->hora,
                'mesa_id' => $reserva->mesa_id,
            ];
            $valores = $this->datosHorario(array_merge($base, $datos));
            $this->validarAsignacion($mesas->get($valores['mesa_id']), $valores, $id);
            $reserva->fill($valores);
            if (array_key_exists('observaciones', $datos)) $reserva->observaciones = $this->observaciones($datos['observaciones']);
            $reserva->save();
            return $reserva->load('mesa');
        }, 3);
    }

    public function cambiarEstado(int $id, $estado): Reserva
    {
        $estado = $this->estado($estado);
        return Capsule::connection()->transaction(function () use ($id, $estado) {
            [$reserva, $mesas] = $this->bloquearReserva($id);
            if ($reserva->estado === $estado) return $reserva->load('mesa');
            $this->editable($reserva);
            if (in_array($estado, self::ACTIVAS, true)) {
                $valores = $this->datosHorario([
                    'fecha' => $reserva->fecha, 'hora' => $reserva->hora,
                    'mesa_id' => $reserva->mesa_id, 'cantidad_personas' => $reserva->cantidad_personas,
                ]);
                $this->validarAsignacion($mesas->get($reserva->mesa_id), $valores, $id);
            }
            $reserva->estado = $estado;
            $reserva->save();
            return $reserva->load('mesa');
        }, 3);
    }

    public function disponibles(array $filtros): Collection
    {
        $fecha = $this->fecha($filtros['fecha'] ?? null);
        $hora = $this->hora($filtros['hora'] ?? null);
        $this->noPasado($fecha, $hora);
        $cantidad = $this->entero($filtros['cantidad_personas'] ?? null, 'La cantidad de personas');
        return Mesa::where('estado', '!=', 'fuera_servicio')->where('capacidad', '>=', $cantidad)
            ->whereDoesntHave('reservas', function ($consulta) use ($fecha, $hora): void {
                $consulta->where('fecha', $fecha)->where('hora', $hora)->whereIn('estado', self::ACTIVAS);
            })->orderBy('id')->get();
    }

    private function datosHorario(array $datos): array
    {
        $fecha = $this->fecha($datos['fecha'] ?? null);
        $hora = $this->hora($datos['hora'] ?? null);
        $this->noPasado($fecha, $hora);
        return [
            'fecha' => $fecha, 'hora' => $hora,
            'mesa_id' => $this->entero($datos['mesa_id'] ?? null, 'La mesa'),
            'cantidad_personas' => $this->entero($datos['cantidad_personas'] ?? null, 'La cantidad de personas'),
        ];
    }

    private function validarAsignacion(Mesa $mesa, array $valores, ?int $excluir = null): void
    {
        if ($mesa->estado === 'fuera_servicio') throw new DomainException('La mesa está fuera de servicio.', 409);
        if ($valores['cantidad_personas'] > $mesa->capacidad) throw new DomainException('La cantidad de personas supera la capacidad de la mesa.', 422);
        $consulta = Reserva::where('mesa_id', $mesa->id)->where('fecha', $valores['fecha'])
            ->where('hora', $valores['hora'])->whereIn('estado', self::ACTIVAS);
        if ($excluir !== null) $consulta->where('id', '!=', $excluir);
        // Lectura actual, no una instantánea anterior al bloqueo de la mesa.
        if ($consulta->lockForUpdate()->first(['id'])) {
            throw new DomainException('La mesa ya tiene una reserva para esa fecha y hora.', 409);
        }
    }

    private function bloquearMesa(int $id): Mesa
    {
        $mesa = Mesa::whereKey($id)->lockForUpdate()->first();
        if (!$mesa) throw new DomainException('La mesa no existe.', 404);
        return $mesa;
    }

    private function bloquearReserva(int $id, $mesaNueva = null): array
    {
        $inicial = Reserva::find($id);
        if (!$inicial) throw new DomainException('La reserva no existe.', 404);
        $destino = $mesaNueva === null ? $inicial->mesa_id : $this->entero($mesaNueva, 'La mesa');
        $ids = array_values(array_unique([$inicial->mesa_id, $destino]));
        sort($ids);
        // Siempre se bloquean primero las mesas, en orden, y después la reserva.
        $mesas = Mesa::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if (!$mesas->has($destino)) throw new DomainException('La mesa no existe.', 404);
        $reserva = Reserva::whereKey($id)->lockForUpdate()->first();
        if (!$reserva || $reserva->mesa_id !== $inicial->mesa_id) {
            throw new DomainException('La reserva cambió durante la operación. Intenta nuevamente.', 409);
        }
        return [$reserva, $mesas];
    }

    private function editable(Reserva $reserva): void
    {
        if (!in_array($reserva->estado, self::ACTIVAS, true)) {
            throw new DomainException('Una reserva cancelada o finalizada no se puede modificar ni reactivar.', 409);
        }
    }

    private function entero($valor, string $nombre): int
    {
        if (!is_int($valor) || $valor < 1 || $valor > 2147483647) {
            throw new DomainException($nombre . ' debe ser un entero positivo.', 422);
        }
        return $valor;
    }

    private function texto($valor, string $nombre, int $maximo): string
    {
        if (!is_string($valor) || trim($valor) === '' || mb_strlen(trim($valor)) > $maximo) {
            throw new DomainException($nombre . ' es obligatorio y admite hasta ' . $maximo . ' caracteres.', 422);
        }
        return trim($valor);
    }

    private function observaciones($valor): ?string
    {
        if ($valor === null) return null;
        if (!is_string($valor) || strlen($valor) > 65535) throw new DomainException('Las observaciones no son válidas.', 422);
        return trim($valor);
    }

    private function fecha($valor): string
    {
        if (!is_string($valor)) throw new DomainException('La fecha debe tener formato AAAA-MM-DD.', 422);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) throw new DomainException('La fecha no es válida.', 422);
        return $valor;
    }

    private function hora($valor): string
    {
        $longitud = is_string($valor) ? strlen($valor) : 0;
        $mensaje = 'La hora debe tener formato HH:MM o HH:MM:SS.';
        if ($longitud !== 5 && $longitud !== 8) {
            throw new DomainException($mensaje, 422);
        }

        $partes = explode(':', $valor);
        if (count($partes) !== ($longitud === 5 ? 2 : 3)) {
            throw new DomainException($mensaje, 422);
        }

        foreach ($partes as $i => $parte) {
            $maximo = $i === 0 ? 23 : 59;
            if (strlen($parte) !== 2 || strspn($parte, '0123456789') !== 2 || (int) $parte > $maximo) {
                throw new DomainException($mensaje, 422);
            }
        }
        return $longitud === 5 ? $valor . ':00' : $valor;
    }

    private function noPasado(string $fecha, string $hora): void
    {
        if ($fecha . ' ' . $hora < (new DateTimeImmutable())->format('Y-m-d H:i:s')) {
            throw new DomainException('No se permiten reservas en fechas u horas pasadas.', 422);
        }
    }

    private function estado($valor): string
    {
        if (!is_string($valor) || !in_array($valor, self::ESTADOS, true)) throw new DomainException('El estado de reserva no es válido.', 422);
        return $valor;
    }

    private function campos(array $datos, array $permitidos): void
    {
        if (array_diff(array_keys($datos), $permitidos) !== []) throw new DomainException('La solicitud contiene campos no permitidos.', 422);
    }
}

