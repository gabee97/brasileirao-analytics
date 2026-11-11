<?php

namespace App\Support;

use InvalidArgumentException;
use JsonException;

class JsonArrayFileReader
{
    public function read(string $path, string $label): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Arquivo de {$label} não encontrado ou sem permissão de leitura: {$path}");
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("JSON de {$label} inválido: {$exception->getMessage()}", 0, $exception);
        }

        if (! is_array($data) || ! array_is_list($data)) {
            throw new InvalidArgumentException("O JSON de {$label} deve conter uma lista na raiz.");
        }

        return $data;
    }
}
