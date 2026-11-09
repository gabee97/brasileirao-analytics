<?php

namespace Tests\Unit\Services;

use App\Services\EloRatingService;
use PHPUnit\Framework\TestCase;

class EloRatingServiceTest extends TestCase
{
    public function test_home_win_updates_both_ratings(): void
    {
        $service = new EloRatingService();

        $result = $service->processMatch(
            1500,
            1500,
            2,
            1
        );

        $this->assertEqualsWithDelta(
            1508.012064,
            $result['home_elo_after'],
            0.0001
        );

        $this->assertEqualsWithDelta(
            1491.987936,
            $result['away_elo_after'],
            0.0001
        );

        // A soma dos ratings deve permanecer constante
        $this->assertEqualsWithDelta(
            3000,
            $result['home_elo_after']
                + $result['away_elo_after'],
            0.0001
        );
    }


    public function test_draw_updates_both_ratings(): void
    {
        $service = new EloRatingService();

        $result = $service->processMatch(
            1500,
            1500,
            1,
            1
        );

        // Mandante perde Elo mesmo empatando
        $this->assertEqualsWithDelta(
            1498.012064,
            $result['home_elo_after'],
            0.0001
        );

        // Visitante ganha Elo pelo empate fora
        $this->assertEqualsWithDelta(
            1501.987936,
            $result['away_elo_after'],
            0.0001
        );

        // A soma dos ratings permanece constante
        $this->assertEqualsWithDelta(
            3000,
            $result['home_elo_after']
                + $result['away_elo_after'],
            0.0001
        );
    }

    public function test_away_win_updates_both_ratings(): void
    {
        $service = new EloRatingService();

        $result = $service->processMatch(
            1500,
            1500,
            0,
            2
        );

        // Mandante perde Elo pela derrota
        $this->assertEqualsWithDelta(
            1488.012064,
            $result['home_elo_after'],
            0.0001
        );

        // Visitante ganha Elo pela vitória fora
        $this->assertEqualsWithDelta(
            1511.987936,
            $result['away_elo_after'],
            0.0001
        );

        // A soma dos ratings permanece constante
        $this->assertEqualsWithDelta(
            3000,
            $result['home_elo_after']
                + $result['away_elo_after'],
            0.0001
        );
    }


    public function test_calculate_processes_all_matches(): void
    {
        $service = new EloRatingService();

        $teams = require dirname(__DIR__, 2)
            .'/Fixtures/teams.php';

        $matches = require dirname(__DIR__, 2)
            .'/Fixtures/matches.php';

        $result = $service->calculate($teams, $matches);

        $this->assertCount(4, $result['ranking']);
        $this->assertCount(4, $result['history']);

        $this->assertSame(
            'aurora',
            $result['ranking'][0]['id']
        );

        $this->assertEqualsWithDelta(
            1519.83290456,
            $result['ranking'][0]['elo'],
            0.0001
        );

        $this->assertEqualsWithDelta(
            6000,
            array_sum(array_column($result['ranking'], 'elo')),
            0.0001
        );
    }


    public function test_calculate_processes_matches_chronologically(): void
    {
        $service = new EloRatingService();

        $teams = require dirname(__DIR__, 2)
            .'/Fixtures/teams.php';

        $matches = require dirname(__DIR__, 2)
            .'/Fixtures/matches.php';

        // Inverte a ordem original das partidas
        $reversedMatches = array_reverse($matches);

        $result = $service->calculate(
            $teams,
            $reversedMatches
        );

        // Mesmo invertendo a entrada, a primeira
        // partida processada deve continuar sendo a ID 1
        $this->assertSame(
            1,
            $result['history'][0]['match_id']
        );

        $this->assertSame(
            4,
            $result['history'][3]['match_id']
        );

        $this->assertEqualsWithDelta(
            1519.83290456,
            $result['ranking'][0]['elo'],
            0.0001
        );
    }

}
