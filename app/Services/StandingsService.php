<?php

namespace App\Services;

use BadMethodCallException;

class StandingsService
{
    /**
     * Organizar a classificação a partir de partidas e equipes.
     * TODO: definir o formato das entradas e a representação da classificação.
     * TODO: contabilizar pontos, vitórias, gols e saldo.
     * TODO: aplicar os critérios de desempate do regulamento escolhido.
     * TODO: permitir uso na classificação observada e ao final de cada simulação.
     *
     * Os contratos de arrays são provisórios; definir com os primeiros testes matemáticos.
     * Nenhum cálculo é executado nesta etapa.
     *
     * @throws BadMethodCallException
     */
    public function calculate(array $teams, array $matches): array
    {
        throw new BadMethodCallException('Funcionalidade ainda não implementada.');
    }
}
