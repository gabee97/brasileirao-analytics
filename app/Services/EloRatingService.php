<?php

namespace App\Services;

use BadMethodCallException;

class EloRatingService
{
    /**
     * Calcular a força Elo a partir de equipes, partidas e parâmetros explícitos.
     * TODO: definir o formato dos arrays e validar entradas.
     * TODO: considerar força dos adversários e mando de campo.
     * TODO: atualizar o Elo após cada partida e permitir ajustes do modelo.
     *
     * Os contratos de arrays são provisórios; definir com os primeiros testes matemáticos.
     * Nenhum cálculo é executado nesta etapa.
     *
     * @throws BadMethodCallException
     */
    public function calculate(array $teams, array $matches, array $parameters = []): array
    {
        throw new BadMethodCallException('Funcionalidade ainda não implementada.');
    }

    /**
     * Calcula a pontuação Elo esperada do mandante.
     *
     * Retorna um valor entre 0 e 1, considerando a força
     * das equipes e a vantagem de mando de campo.
     *
     * Não representa a probabilidade isolada de vitória
     * nem prevê o placar da partida.
    */
    public function calculateExpectedEloScore(
        float $homeElo,
        float $awayElo,
        float $homeAdvantage = 70
    ): float {
        $adjustedHomeElo = $homeElo + $homeAdvantage;

        return 1 / (
            1 + pow(
                10,
                ($awayElo - $adjustedHomeElo) / 400
            )
        );
    }

}
