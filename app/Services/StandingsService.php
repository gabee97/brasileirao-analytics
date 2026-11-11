<?php

namespace App\Services;

use InvalidArgumentException;

class StandingsService
{
    public function calculate(array $teams, array $matches): array
    {
        $standings = $this->initializeStandings($teams);

        foreach ($matches as $index => $match) {
            $this->validateMatch($match, $index, $standings);
            $homeId = $match['home_team_id'];
            $awayId = $match['away_team_id'];
            $homeGoals = $match['home_goals'];
            $awayGoals = $match['away_goals'];

            $standings[$homeId]['played']++;
            $standings[$awayId]['played']++;
            $standings[$homeId]['goals_for'] += $homeGoals;
            $standings[$homeId]['goals_against'] += $awayGoals;
            $standings[$awayId]['goals_for'] += $awayGoals;
            $standings[$awayId]['goals_against'] += $homeGoals;

            if ($homeGoals > $awayGoals) {
                $standings[$homeId]['wins']++;
                $standings[$homeId]['points'] += 3;
                $standings[$awayId]['losses']++;
            } elseif ($homeGoals < $awayGoals) {
                $standings[$awayId]['wins']++;
                $standings[$awayId]['points'] += 3;
                $standings[$homeId]['losses']++;
            } else {
                $standings[$homeId]['draws']++;
                $standings[$awayId]['draws']++;
                $standings[$homeId]['points']++;
                $standings[$awayId]['points']++;
            }
        }

        $ranking = array_values($standings);
        foreach ($ranking as &$row) {
            $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
        }
        unset($row);

        usort($ranking, $this->baseComparator(...));
        $ranking = $this->applyHeadToHead($ranking, $matches);

        foreach ($ranking as $index => &$row) {
            $row = ['position' => $index + 1] + $row;
        }
        unset($row);

        return $ranking;
    }

    private function initializeStandings(array $teams): array
    {
        if ($teams === []) {
            throw new InvalidArgumentException('A lista de equipes não pode estar vazia.');
        }

        $standings = [];
        foreach ($teams as $index => $team) {
            if (! is_array($team)) {
                throw new InvalidArgumentException("Equipe na posição {$index} deve ser um objeto.");
            }
            $id = $team['id'] ?? null;
            $name = $team['name'] ?? null;
            if (! is_string($id) || trim($id) === '' || ! is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException("Equipe na posição {$index} deve possuir id e name não vazios.");
            }
            if (isset($standings[$id])) {
                throw new InvalidArgumentException("Identificador de equipe duplicado: {$id}.");
            }
            $standings[$id] = [
                'id' => $id, 'name' => $name, 'points' => 0, 'played' => 0,
                'wins' => 0, 'draws' => 0, 'losses' => 0,
                'goals_for' => 0, 'goals_against' => 0,
            ];
        }

        return $standings;
    }

    private function validateMatch(mixed $match, int $index, array $standings): void
    {
        if (! is_array($match)) {
            throw new InvalidArgumentException("Partida na posição {$index} deve ser um objeto.");
        }
        foreach (['home_team_id', 'away_team_id'] as $field) {
            if (! isset($match[$field]) || ! is_string($match[$field]) || trim($match[$field]) === '') {
                throw new InvalidArgumentException("Partida na posição {$index} possui {$field} inválido.");
            }
            if (! isset($standings[$match[$field]])) {
                throw new InvalidArgumentException("Partida na posição {$index} referencia equipe desconhecida: {$match[$field]}.");
            }
        }
        if ($match['home_team_id'] === $match['away_team_id']) {
            throw new InvalidArgumentException("Partida na posição {$index} não pode ter a mesma equipe nos dois lados.");
        }
        foreach (['home_goals', 'away_goals'] as $field) {
            if (! array_key_exists($field, $match) || ! is_int($match[$field]) || $match[$field] < 0) {
                throw new InvalidArgumentException("Partida na posição {$index} possui {$field} inválido.");
            }
        }
    }

    private function baseComparator(array $a, array $b): int
    {
        foreach (['points', 'wins', 'goal_difference', 'goals_for'] as $field) {
            if (($comparison = $b[$field] <=> $a[$field]) !== 0) {
                return $comparison;
            }
        }

        return $this->deterministicComparator($a, $b);
    }

    private function applyHeadToHead(array $ranking, array $matches): array
    {
        $start = 0;
        while ($start < count($ranking)) {
            $end = $start + 1;
            while ($end < count($ranking) && $this->sameBaseCriteria($ranking[$start], $ranking[$end])) {
                $end++;
            }

            if ($end - $start === 2) {
                $first = $ranking[$start];
                $second = $ranking[$start + 1];
                $goals = [$first['id'] => 0, $second['id'] => 0];
                $meetings = 0;
                foreach ($matches as $match) {
                    $ids = [$match['home_team_id'], $match['away_team_id']];
                    if (in_array($first['id'], $ids, true) && in_array($second['id'], $ids, true)) {
                        $goals[$match['home_team_id']] += $match['home_goals'];
                        $goals[$match['away_team_id']] += $match['away_goals'];
                        $meetings++;
                    }
                }
                if ($meetings === 2 && $goals[$second['id']] > $goals[$first['id']]) {
                    [$ranking[$start], $ranking[$start + 1]] = [$second, $first];
                }
            }
            $start = $end;
        }

        return $ranking;
    }

    private function sameBaseCriteria(array $a, array $b): bool
    {
        foreach (['points', 'wins', 'goal_difference', 'goals_for'] as $field) {
            if ($a[$field] !== $b[$field]) {
                return false;
            }
        }

        return true;
    }

    private function deterministicComparator(array $a, array $b): int
    {
        return strcasecmp($a['name'], $b['name']) ?: strcmp($a['id'], $b['id']);
    }
}
