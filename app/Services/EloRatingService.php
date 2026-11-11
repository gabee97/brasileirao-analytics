<?php

namespace App\Services;

use InvalidArgumentException;
use LogicException;

class EloRatingService
{
    public function calculate(array $teams, array $matches, array $parameters = []): array
    {
        $k = $this->finiteNumber($parameters['k'] ?? 20, 'O fator K');
        $homeAdvantage = $this->finiteNumber($parameters['home_advantage'] ?? 70, 'A vantagem de mando');
        if ($k <= 0) {
            throw new InvalidArgumentException('O fator K deve ser um número positivo e finito.');
        }

        $ratings = $this->initializeRatings($teams);
        $matches = $this->validateAndSortMatches($matches, $ratings);
        $history = [];

        foreach ($matches as $match) {
            $homeId = $match['home_team_id'];
            $awayId = $match['away_team_id'];
            $sumBefore = $ratings[$homeId]['elo'] + $ratings[$awayId]['elo'];
            $result = $this->processMatch(
                $ratings[$homeId]['elo'],
                $ratings[$awayId]['elo'],
                $match['home_goals'],
                $match['away_goals'],
                $k,
                $homeAdvantage,
            );
            $sumAfter = $result['home_elo_after'] + $result['away_elo_after'];
            if (abs($sumBefore - $sumAfter) > 1.0E-9) {
                throw new LogicException("A soma dos ratings não foi conservada na partida {$match['id']}.");
            }

            $ratings[$homeId]['elo'] = $result['home_elo_after'];
            $ratings[$awayId]['elo'] = $result['away_elo_after'];
            $history[] = [
                'match_id' => $match['id'], 'played_at' => $match['played_at'],
                'home_team_id' => $homeId, 'away_team_id' => $awayId, ...$result,
            ];
        }

        $ranking = array_values($ratings);
        foreach ($ranking as &$team) {
            $team['variation'] = $team['elo'] - $team['initial_elo'];
        }
        unset($team);
        usort($ranking, static fn (array $a, array $b): int => $b['elo'] <=> $a['elo']
            ?: strcasecmp($a['name'], $b['name']) ?: strcmp($a['id'], $b['id']));

        return ['ranking' => $ranking, 'history' => $history];
    }

    public function calculateExpectedEloScore(float $homeElo, float $awayElo, float $homeAdvantage = 70): float
    {
        if (! is_finite($homeElo) || ! is_finite($awayElo) || ! is_finite($homeAdvantage)) {
            throw new InvalidArgumentException('Ratings e vantagem de mando devem ser finitos.');
        }

        return 1 / (1 + pow(10, ($awayElo - ($homeElo + $homeAdvantage)) / 400));
    }

    public function updateRating(float $currentElo, float $expectedScore, float $actualScore, float $k = 20): float
    {
        $rating = $currentElo + $k * ($actualScore - $expectedScore);
        if (! is_finite($rating)) {
            throw new InvalidArgumentException('A atualização produziu um rating não finito.');
        }

        return $rating;
    }

    public function processMatch(
        float $homeElo,
        float $awayElo,
        int $homeGoals,
        int $awayGoals,
        float $k = 20,
        float $homeAdvantage = 70,
    ): array {
        if ($homeGoals < 0 || $awayGoals < 0) {
            throw new InvalidArgumentException('A quantidade de gols não pode ser negativa.');
        }
        if (! is_finite($k) || $k <= 0) {
            throw new InvalidArgumentException('O fator K deve ser um número positivo e finito.');
        }
        if (! is_finite($homeAdvantage)) {
            throw new InvalidArgumentException('A vantagem de mando deve ser finita.');
        }

        $expectedHome = $this->calculateExpectedEloScore($homeElo, $awayElo, $homeAdvantage);
        $expectedAway = 1 - $expectedHome;
        $actualHome = match (true) {
            $homeGoals > $awayGoals => 1.0,
            $homeGoals === $awayGoals => 0.5,
            default => 0.0,
        };
        $actualAway = 1 - $actualHome;
        $newHomeElo = $this->updateRating($homeElo, $expectedHome, $actualHome, $k);
        $newAwayElo = $this->updateRating($awayElo, $expectedAway, $actualAway, $k);

        return [
            'home_elo_before' => $homeElo, 'away_elo_before' => $awayElo,
            'home_elo_after' => $newHomeElo, 'away_elo_after' => $newAwayElo,
            'home_variation' => $newHomeElo - $homeElo,
            'away_variation' => $newAwayElo - $awayElo,
        ];
    }

    private function initializeRatings(array $teams): array
    {
        if ($teams === []) {
            throw new InvalidArgumentException('A lista de equipes não pode estar vazia.');
        }
        $ratings = [];
        foreach ($teams as $index => $team) {
            if (! is_array($team)) {
                throw new InvalidArgumentException("Equipe na posição {$index} deve ser um objeto.");
            }
            foreach (['id', 'name', 'initial_elo'] as $field) {
                if (! array_key_exists($field, $team)) {
                    throw new InvalidArgumentException("Equipe na posição {$index} não possui {$field}.");
                }
            }
            if (! is_string($team['id']) || trim($team['id']) === '' || ! is_string($team['name']) || trim($team['name']) === '') {
                throw new InvalidArgumentException("Equipe na posição {$index} deve possuir id e name não vazios.");
            }
            if (isset($ratings[$team['id']])) {
                throw new InvalidArgumentException("Identificador de equipe duplicado: {$team['id']}.");
            }
            $initialElo = $this->finiteNumber($team['initial_elo'], "initial_elo da equipe {$team['id']}");
            if ($initialElo !== 1500.0) {
                throw new InvalidArgumentException("O initial_elo da equipe {$team['id']} deve ser 1500 nesta execução.");
            }
            $ratings[$team['id']] = [
                'id' => $team['id'], 'name' => $team['name'],
                'initial_elo' => $initialElo, 'elo' => $initialElo,
            ];
        }

        return $ratings;
    }

    private function validateAndSortMatches(array $matches, array $ratings): array
    {
        $ids = [];
        foreach ($matches as $index => $match) {
            if (! is_array($match)) {
                throw new InvalidArgumentException("Partida na posição {$index} deve ser um objeto.");
            }
            foreach (['id', 'played_at', 'home_team_id', 'away_team_id', 'home_goals', 'away_goals'] as $field) {
                if (! array_key_exists($field, $match)) {
                    throw new InvalidArgumentException("Partida na posição {$index} não possui {$field}.");
                }
            }
            $id = (string) $match['id'];
            if ($id === '' || isset($ids[$id])) {
                throw new InvalidArgumentException("Partida na posição {$index} possui id vazio ou duplicado.");
            }
            $ids[$id] = true;
            if (! is_string($match['played_at']) || strtotime($match['played_at']) === false) {
                throw new InvalidArgumentException("Partida {$id} possui played_at inválido.");
            }
            foreach (['home_team_id', 'away_team_id'] as $field) {
                if (! is_string($match[$field]) || ! isset($ratings[$match[$field]])) {
                    throw new InvalidArgumentException("Partida {$id} contém equipe não cadastrada em {$field}.");
                }
            }
            if ($match['home_team_id'] === $match['away_team_id']) {
                throw new InvalidArgumentException("Partida {$id} não pode ter a mesma equipe nos dois lados.");
            }
            foreach (['home_goals', 'away_goals'] as $field) {
                if (! is_int($match[$field]) || $match[$field] < 0) {
                    throw new InvalidArgumentException("Partida {$id} possui {$field} inválido.");
                }
            }
        }

        usort($matches, static fn (array $a, array $b): int => strcmp($a['played_at'], $b['played_at'])
            ?: strnatcmp((string) $a['id'], (string) $b['id']));

        return $matches;
    }

    private function finiteNumber(mixed $value, string $label): float
    {
        if ((! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value)))
            || ! is_finite($number = (float) $value)) {
            throw new InvalidArgumentException("{$label} deve ser numérico e finito.");
        }

        return $number;
    }
}
