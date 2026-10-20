<?php

namespace App\Services;

use BadMethodCallException;

class MonteCarloSimulator
{
    /**
     * Simular os jogos restantes de um campeonato.
     * TODO: definir entradas, quantidade de execuções e controle de aleatoriedade.
     * TODO: executar milhares de simulações usando o modelo de partidas.
     * TODO: organizar cada classificação com StandingsService e consolidar resultados.
     *
     * Os contratos de arrays são provisórios; definir com os primeiros testes matemáticos.
     * Nenhum cálculo é executado nesta etapa.
     *
     * @throws BadMethodCallException
     */
    public function simulate(array $teams, array $remainingMatches, array $parameters = []): array
    {
        throw new BadMethodCallException('Funcionalidade ainda não implementada.');
    }
}
