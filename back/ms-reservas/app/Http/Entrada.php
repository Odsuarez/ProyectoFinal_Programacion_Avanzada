<?php
declare(strict_types=1);
namespace App\Http;
use DomainException;
use Psr\Http\Message\ServerRequestInterface;

final class Entrada
{
    public static function cuerpo(ServerRequestInterface $request): array
    {
        $tipo = strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'))[0]));
        if ($tipo !== 'application/json') {
            throw new DomainException('Envía el cuerpo como application/json.', 415);
        }
        // Se distingue un objeto JSON de un arreglo o valor escalar.
        try {
            $objeto = json_decode((string) $request->getBody(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new DomainException('El JSON no es válido.', 400);
        }
        if (!$objeto instanceof \stdClass) {
            throw new DomainException('El cuerpo debe ser un objeto JSON.', 422);
        }
        return get_object_vars($objeto);
    }

    public static function id($valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || is_bool($valor) || is_float($valor)) {
            throw new DomainException('El identificador debe ser un entero positivo.', 422);
        }
        return $id;
    }
}

