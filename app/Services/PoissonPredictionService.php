<?php

namespace App\Services;

use BadMethodCallException;

class PoissonPredictionService
{
    /**
     * Estimar a previsão de uma partida.
     * TODO: definir os dados de ataque/defesa e os parâmetros do modelo.
     * TODO: estimar gols esperados e probabilidades dos placares.
     * TODO: consolidar vitória, empate e derrota.
     *
     * Os contratos de arrays são provisórios; definir com os primeiros testes matemáticos.
     * Nenhum cálculo é executado nesta etapa.
     *
     * @throws BadMethodCallException
     */
    public function predict(array $homeTeam, array $awayTeam, array $parameters = []): array
    {
        throw new BadMethodCallException('Funcionalidade ainda não implementada.');
    }
}
