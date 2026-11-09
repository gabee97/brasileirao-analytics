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
    public function calculate(
        array $teams,
        array $matches,
        array $parameters = []
    ): array {
        $k = $parameters['k'] ?? 20;
        $homeAdvantage = $parameters['home_advantage'] ?? 70;

        // 1. Inicializa os ratings das equipes
        $ratings = [];

        foreach ($teams as $team) {
            $ratings[$team['id']] = [
                'id' => $team['id'],
                'name' => $team['name'],
                'elo' => (float) $team['initial_elo'],
            ];
        }

        // 2. Ordena as partidas cronologicamente
        usort($matches, static function ($a, $b) {
            return strtotime($a['played_at'])
                <=> strtotime($b['played_at']);
        });

        $history = [];

        // 3. Processa cada partida
        foreach ($matches as $match) {
            $homeId = $match['home_team_id'];
            $awayId = $match['away_team_id'];

            if (!isset($ratings[$homeId], $ratings[$awayId])) {
                throw new \InvalidArgumentException(
                    'Partida contém equipe não cadastrada.'
                );
            }

            $result = $this->processMatch(
                $ratings[$homeId]['elo'],
                $ratings[$awayId]['elo'],
                $match['home_goals'],
                $match['away_goals'],
                $k,
                $homeAdvantage
            );

            // 4. Atualiza os ratings acumulados
            $ratings[$homeId]['elo'] = $result['home_elo_after'];
            $ratings[$awayId]['elo'] = $result['away_elo_after'];

            // 5. Guarda o histórico da partida
            $history[] = [
                'match_id' => $match['id'],
                'played_at' => $match['played_at'],
                'home_team_id' => $homeId,
                'away_team_id' => $awayId,
                ...$result,
            ];
        }

        // 6. Ordena as equipes pelo Elo final
        $ranking = array_values($ratings);

        usort($ranking, static function ($a, $b) {
            return $b['elo'] <=> $a['elo'];
        });

        return [
            'ranking' => $ranking,
            'history' => $history,
        ];
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

    /**
     * Atualiza a pontuação Elo de um clube após uma partida.
     *
     * @param float $currentElo Elo antes do jogo
     * @param float $expectedScore Expectativa Elo (0 a 1)
     * @param float $actualScore Resultado real (0, 0.5 ou 1)
     * @param float $k Fator de atualização
     */
    public function updateRating(
        float $currentElo,
        float $expectedScore,
        float $actualScore,
        float $k = 20
    ): float {
        return $currentElo + $k * ($actualScore - $expectedScore);
    }


    public function processMatch(
        float $homeElo,
        float $awayElo,
        int $homeGoals,
        int $awayGoals,
        float $k = 20,
        float $homeAdvantage = 70
    ): array {
        // 1. Calcula a expectativa do mandante
        $expectedHome = $this->calculateExpectedEloScore(
            $homeElo,
            $awayElo,
            $homeAdvantage
        );

        // 2. A expectativa do visitante é complementar
        $expectedAway = 1 - $expectedHome;

        // 3. Identifica o resultado real do mandante
        $actualHome = match (true) {
            $homeGoals > $awayGoals => 1.0,
            $homeGoals === $awayGoals => 0.5,
            default => 0.0,
        };

        // 4. Resultado do visitante
        $actualAway = 1 - $actualHome;

        // 5. Atualiza o Elo dos dois clubes
        $newHomeElo = $this->updateRating(
            $homeElo,
            $expectedHome,
            $actualHome,
            $k
        );

        $newAwayElo = $this->updateRating(
            $awayElo,
            $expectedAway,
            $actualAway,
            $k
        );

        return [
            'home_elo_before' => $homeElo,
            'away_elo_before' => $awayElo,
            'home_elo_after' => $newHomeElo,
            'away_elo_after' => $newAwayElo,
            'home_variation' => $newHomeElo - $homeElo,
            'away_variation' => $newAwayElo - $awayElo,
        ];
    }


    
}
