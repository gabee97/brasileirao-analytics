<?php

namespace App\Services;

use InvalidArgumentException;

class EloBacktestService
{
    public function __construct(
        private readonly EloRatingService $eloService
    ) {}

    public function evaluateMatch(
        float $homeElo,
        float $awayElo,
        int $homeGoals,
        int $awayGoals,
        float $homeAdvantage = 70
    ): array {
        if ($homeGoals < 0 || $awayGoals < 0) {
            throw new InvalidArgumentException(
                'Os gols não podem ser negativos.'
            );
        }

        // Expectativa calculada antes do resultado
        $expected = $this->eloService
            ->calculateExpectedEloScore(
                $homeElo,
                $awayElo,
                $homeAdvantage
            );

        // Resultado real na escala Elo
        $actual = match (true) {
            $homeGoals > $awayGoals => 1.0,
            $homeGoals === $awayGoals => 0.5,
            default => 0.0,
        };

        // Erro quadrático da expectativa
        $squaredError = ($actual - $expected) ** 2;

        return [
            'expected' => $expected,
            'actual' => $actual,
            'squared_error' => $squaredError,
        ];
    }

    public function calculateMse(array $evaluations): float
    {
        if (empty($evaluations)) {
            throw new InvalidArgumentException(
                'Não é possível calcular o MSE sem partidas.'
            );
        }

        $totalError = 0.0;

        foreach ($evaluations as $evaluation) {
            $totalError += $evaluation['squared_error'];
        }

        return $totalError / count($evaluations);
    }


    public function backtest(
        array $teams,
        array $matches,
        float $k = 20,
        float $homeAdvantage = 70
    ): array {
        // 1. Processa o campeonato usando o Elo existente.
        $result = $this->eloService->calculate(
            $teams,
            $matches,
            [
                'k' => $k,
                'home_advantage' => $homeAdvantage,
            ]
        );

        // 2. Indexa as partidas pelo ID para consultar os placares.
        $matchesById = [];

        foreach ($matches as $match) {
            $matchesById[(string) $match['id']] = $match;
        }

        // 3. Avalia cada partida usando os ratings anteriores.
        $evaluations = [];

        foreach ($result['history'] as $history) {
            $match = $matchesById[(string) $history['match_id']];

            $evaluation = $this->evaluateMatch(
                $history['home_elo_before'],
                $history['away_elo_before'],
                $match['home_goals'],
                $match['away_goals'],
                $homeAdvantage
            );

            $evaluations[] = [
                'match_id' => $history['match_id'],
                'played_at' => $history['played_at'],
                ...$evaluation,
            ];
        }

        // 4. Retorna o erro médio e os detalhes das avaliações.
        $eloMse = $this->calculateMse($evaluations);
        $baselineMse = $this->calculateBaselineMse($evaluations);

        $homeBaselineMse = $this->calculateHomeBaselineMse(
            $evaluations,
            $homeAdvantage
        );
        
        return [
            'matches_evaluated' => count($evaluations),
            'mse' => $eloMse,
            'baseline_mse' => $baselineMse,
            'home_baseline_mse' => $homeBaselineMse,
            'evaluations' => $evaluations,
        ];

    }
    
    public function calculateBaselineMse(array $evaluations): float
    {
        if (empty($evaluations)) {
            throw new InvalidArgumentException(
                'Não é possível calcular o baseline sem partidas.'
            );
        }

        $totalError = 0.0;

        foreach ($evaluations as $evaluation) {
            $actual = $evaluation['actual'];
            $expected = 0.5;

            $totalError += ($actual - $expected) ** 2;
        }

        return $totalError / count($evaluations);
    }

    public function calculateHomeBaselineMse(
        array $evaluations,
        float $homeAdvantage = 70
    ): float {
        if (empty($evaluations)) {
            throw new InvalidArgumentException(
                'Não é possível calcular o baseline sem partidas.'
            );
        }

        // Todos os clubes possuem a mesma força.
        // A única diferença é o mando de campo.
        $expected = $this->eloService
            ->calculateExpectedEloScore(
                1500,
                1500,
                $homeAdvantage
            );

        $totalError = 0.0;

        foreach ($evaluations as $evaluation) {
            $actual = $evaluation['actual'];

            $totalError += ($actual - $expected) ** 2;
        }

        return $totalError / count($evaluations);
    }
}
