<?php

namespace App\Services;

use InvalidArgumentException;

class EloSeasonTransitionService
{
    public function prepareNextSeason(
        array $nextSeasonTeams,
        array $previousRanking,
        float $retention = 1.0
    ): array {
        if (! is_finite($retention) || $retention < 0 || $retention > 1) {
            throw new InvalidArgumentException(
                'A retenção do Elo deve estar entre 0 e 1.'
            );
        }

        // Indexa o Elo final da temporada anterior pelo ID.
        $previousRatings = [];

        foreach ($previousRanking as $team) {
            $id = $team['id'];
            $elo = (float) $team['elo'];

            if (! is_finite($elo) || $elo <= 0) {
                throw new InvalidArgumentException(
                    "Elo inválido para o clube {$id}."
                );
            }

            if (isset($previousRatings[$id])) {
                throw new InvalidArgumentException(
                    "Clube duplicado no ranking anterior: {$id}."
                );
            }

            $previousRatings[$id] = $elo;
        }

        // Prepara os clubes da nova temporada.
        $preparedTeams = [];

        foreach ($nextSeasonTeams as $team) {
            $id = $team['id'];

            $team['initial_elo'] = isset($previousRatings[$id])
                ? 1500.0 + $retention * ($previousRatings[$id] - 1500.0)
                : 1500.0;

            $preparedTeams[] = $team;
        }

        return $preparedTeams;
    }
}
