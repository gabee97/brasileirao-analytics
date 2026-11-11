<?php

namespace Tests\Unit\Services;

use App\Services\StandingsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StandingsServiceTest extends TestCase
{
    public function test_calculates_standings_from_fixtures(): void
    {
        $teams = require dirname(__DIR__, 2).'/Fixtures/teams.php';
        $matches = require dirname(__DIR__, 2).'/Fixtures/matches.php';

        $ranking = (new StandingsService())->calculate($teams, $matches);

        self::assertSame(['aurora', 'vale', 'serra', 'litoral'], array_column($ranking, 'id'));
        self::assertSame([
            'position' => 1, 'id' => 'aurora', 'name' => 'Aurora FC',
            'points' => 6, 'played' => 2, 'wins' => 2, 'draws' => 0, 'losses' => 0,
            'goals_for' => 5, 'goals_against' => 2, 'goal_difference' => 3,
        ], $ranking[0]);
        self::assertSame(1, $ranking[2]['draws']);
        self::assertSame(1, $ranking[3]['draws']);
    }

    public function test_uses_2025_tie_breakers_in_order(): void
    {
        $teams = [
            ['id' => 'a', 'name' => 'A'], ['id' => 'b', 'name' => 'B'],
            ['id' => 'c', 'name' => 'C'], ['id' => 'd', 'name' => 'D'],
        ];
        $matches = [
            ['home_team_id' => 'a', 'away_team_id' => 'b', 'home_goals' => 2, 'away_goals' => 0],
            ['home_team_id' => 'b', 'away_team_id' => 'a', 'home_goals' => 1, 'away_goals' => 0],
            ['home_team_id' => 'a', 'away_team_id' => 'c', 'home_goals' => 2, 'away_goals' => 1],
            ['home_team_id' => 'b', 'away_team_id' => 'd', 'home_goals' => 3, 'away_goals' => 0],
        ];

        $ranking = (new StandingsService())->calculate($teams, $matches);

        self::assertSame(['a', 'b'], array_slice(array_column($ranking, 'id'), 0, 2));
        self::assertSame($ranking[0]['points'], $ranking[1]['points']);
        self::assertSame($ranking[0]['wins'], $ranking[1]['wins']);
        self::assertSame($ranking[0]['goal_difference'], $ranking[1]['goal_difference']);
        self::assertSame($ranking[0]['goals_for'], $ranking[1]['goals_for']);
    }

    public function test_rejects_duplicate_teams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identificador de equipe duplicado');

        (new StandingsService())->calculate([
            ['id' => 'a', 'name' => 'A'], ['id' => 'a', 'name' => 'Outra A'],
        ], []);
    }

    public function test_rejects_unknown_team_in_match(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('equipe desconhecida');

        (new StandingsService())->calculate(
            [['id' => 'a', 'name' => 'A']],
            [['home_team_id' => 'a', 'away_team_id' => 'b', 'home_goals' => 1, 'away_goals' => 0]],
        );
    }

    public function test_rejects_invalid_goals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('home_goals inválido');

        (new StandingsService())->calculate(
            [['id' => 'a', 'name' => 'A'], ['id' => 'b', 'name' => 'B']],
            [['home_team_id' => 'a', 'away_team_id' => 'b', 'home_goals' => -1, 'away_goals' => 0]],
        );
    }
}
