<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Mesa;
use App\Models\Reserva;
use DomainException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

final class MesaService
{
    public const ESTADOS = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];

    public function listar(): Collection { return Mesa::orderBy('id')->get(); }

    public function buscar(int $id): Mesa
    {
        $mesa = $id > 0 ? Mesa::find($id) : null;
        if (!$mesa) throw new DomainException('La mesa no existe.', 404);
        return $mesa;
    }

    public function crear(array $datos): Mesa
    {
        if (array_diff(array_keys($datos), ['numero', 'capacidad', 'estado']) !== []) {
            throw new DomainException('La solicitud contiene campos no permitidos.', 422);
        }
        $numero = $datos['numero'] ?? null;
        if (!is_string($numero) || trim($numero) === '' || mb_strlen(trim($numero)) > 20) {
            throw new DomainException('El número o nombre de mesa es obligatorio y admite hasta 20 caracteres.', 422);
        }
        $numero = trim($numero);
        $capacidad = $this->validarCapacidad($datos['capacidad'] ?? null);
        $estado = $this->validarEstado($datos['estado'] ?? 'disponible');
        if (Mesa::where('numero', $numero)->exists()) throw new DomainException('Ya existe una mesa con ese número o nombre.', 409);
        try {
            return Mesa::create(['numero' => $numero, 'capacidad' => $capacidad, 'estado' => $estado]);
        } catch (QueryException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                throw new DomainException('Ya existe una mesa con ese número o nombre.', 409);
            }
            throw $error;
        }
    }

    public function editar(int $id, array $datos): Mesa
    {
        if ($datos === [] || array_diff(array_keys($datos), ['capacidad', 'estado']) !== []) {
            throw new DomainException('Solo se puede editar la capacidad y el estado.', 422);
        }
        $capacidad = array_key_exists('capacidad', $datos) ? $this->validarCapacidad($datos['capacidad']) : null;
        $estado = array_key_exists('estado', $datos) ? $this->validarEstado($datos['estado']) : null;
        return Capsule::connection()->transaction(function () use ($id, $capacidad, $estado) {
            $mesa = Mesa::whereKey($id)->lockForUpdate()->first();
            if (!$mesa) throw new DomainException('La mesa no existe.', 404);
            if ($capacidad !== null) {
                $ahora = new \DateTimeImmutable();
                $conflicto = Reserva::where('mesa_id', $mesa->id)
                    ->whereIn('estado', ReservaService::ACTIVAS)
                    ->where('cantidad_personas', '>', $capacidad)
                    ->where(function ($consulta) use ($ahora): void {
                        $consulta->where('fecha', '>', $ahora->format('Y-m-d'))
                            ->orWhere(function ($hoy) use ($ahora): void {
                                $hoy->where('fecha', $ahora->format('Y-m-d'))->where('hora', '>=', $ahora->format('H:i:s'));
                            });
                    })->exists();
                if ($conflicto) throw new DomainException('La capacidad dejaría sin espacio una reserva activa futura. Reasígnala o cancélala primero.', 409);
                $mesa->capacidad = $capacidad;
            }
            if ($estado !== null) $mesa->estado = $estado;
            $mesa->save();
            return $mesa;
        }, 3);
    }

    public function cambiarEstado(int $id, string $estado): Mesa { return $this->editar($id, ['estado' => $estado]); }

    private function validarCapacidad($capacidad): int
    {
        if (!is_int($capacidad) || $capacidad < 1 || $capacidad > 2147483647) {
            throw new DomainException('La capacidad debe ser un entero mayor que cero.', 422);
        }
        return $capacidad;
    }

    private function validarEstado($estado): string
    {
        if (!is_string($estado) || !in_array($estado, self::ESTADOS, true)) throw new DomainException('El estado de la mesa no es válido.', 422);
        return $estado;
    }
}

