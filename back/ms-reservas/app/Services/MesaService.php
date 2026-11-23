<?php
declare(strict_types=1);
namespace App\Services;

use App\Models\Mesa;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

final class MesaService
{
    public const ESTADOS = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];

    public function listar(): Collection
    {
        return Mesa::orderBy('id')->get();
    }

    public function buscar(int $id): Mesa
    {
        $mesa = $id > 0 ? Mesa::find($id) : null;
        if (!$mesa) {
            throw new DomainException('La mesa no existe.', 404);
        }
        return $mesa;
    }

    public function crear(array $datos): Mesa
    {
        $numero = $datos['numero'] ?? null;
        if (!is_string($numero) || trim($numero) === '' || mb_strlen(trim($numero)) > 20) {
            throw new DomainException('El número o nombre de mesa es obligatorio y admite hasta 20 caracteres.', 422);
        }
        $numero = trim($numero);
        $capacidad = $this->validarCapacidad($datos['capacidad'] ?? null);
        $estado = $this->validarEstado($datos['estado'] ?? 'disponible');
        if (Mesa::where('numero', $numero)->exists()) {
            throw new DomainException('Ya existe una mesa con ese número o nombre.', 409);
        }
        try {
            return Mesa::create(['numero' => $numero, 'capacidad' => $capacidad, 'estado' => $estado]);
        } catch (QueryException $error) {
            // La restricción UNIQUE resuelve también dos altas simultáneas.
            if (($error->errorInfo[1] ?? null) === 1062) {
                throw new DomainException('Ya existe una mesa con ese número o nombre.', 409);
            }
            throw $error;
        }
    }

    public function editar(int $id, array $datos): Mesa
    {
        $mesa = $this->buscar($id);
        if ($datos === [] || array_diff(array_keys($datos), ['capacidad', 'estado']) !== []) {
            throw new DomainException('Solo se puede editar la capacidad y el estado.', 422);
        }
        if (array_key_exists('capacidad', $datos)) {
            $mesa->capacidad = $this->validarCapacidad($datos['capacidad']);
        }
        if (array_key_exists('estado', $datos)) {
            $mesa->estado = $this->validarEstado($datos['estado']);
        }
        $mesa->save();
        return $mesa;
    }

    public function cambiarEstado(int $id, string $estado): Mesa
    {
        return $this->editar($id, ['estado' => $estado]);
    }

    private function validarCapacidad($capacidad): int
    {
        if (!is_int($capacidad) || $capacidad < 1 || $capacidad > 2147483647) {
            throw new DomainException('La capacidad debe ser un número entero mayor que cero.', 422);
        }
        return $capacidad;
    }

    private function validarEstado($estado): string
    {
        if (!is_string($estado) || !in_array($estado, self::ESTADOS, true)) {
            throw new DomainException('El estado de la mesa no es válido.', 422);
        }
        return $estado;
    }
}

