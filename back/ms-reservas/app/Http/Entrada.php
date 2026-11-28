<?php

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
        if (is_int($valor) && $valor > 0) {
            return $valor;
        }
        if (is_string($valor)) {
            $texto = trim($valor, " \t\n\r\v\f");
            if ($texto !== '' && $texto[0] === '+') {
                $texto = substr($texto, 1);
            }

            $longitud = strlen($texto);
            $maximo = (string) PHP_INT_MAX;
            if ($longitud > 0 && $texto[0] !== '0'
                && strspn($texto, '0123456789') === $longitud
                && ($longitud < strlen($maximo)
                    || ($longitud === strlen($maximo) && strcmp($texto, $maximo) <= 0))) {
                return (int) $texto;
            }
        }
        throw new DomainException('El identificador debe ser un entero positivo.', 422);
    }
}
