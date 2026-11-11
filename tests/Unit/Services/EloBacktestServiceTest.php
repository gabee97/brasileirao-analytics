<?php

namespace Tests\Unit\Services;

use App\Services\EloBacktestService;
use App\Services\EloRatingService;
use PHPUnit\Framework\TestCase;

class EloBacktestServiceTest extends TestCase
{
    public function test_calculates_squared_error_for_home_win(): void
    {
        $service = new EloBacktestService(
            new EloRatingService()
        );

        $result = $service->evaluateMatch(
            1500,
            1500,
            2,
            1,
            0
        );

        $this->assertEqualsWithDelta(
            0.5,
            $result['expected'],
            0.000001
        );

        $this->assertSame(1.0, $result['actual']);

        $this->assertEqualsWithDelta(
            0.25,
            $result['squared_error'],
            0.000001
        );
    }

    public function test_perfect_draw_expectation_has_zero_error(): void
    {
        $service = new EloBacktestService(
            new EloRatingService()
        );

        $result = $service->evaluateMatch(
            1500,
            1500,
            1,
            1,
            0
        );

        $this->assertSame(0.5, $result['actual']);

        $this->assertEqualsWithDelta(
            0,
            $result['squared_error'],
            0.000001
        );
    }


    public function test_calculates_mean_squared_error(): void
    {
        $service = new EloBacktestService(
            new EloRatingService()
        );

        $evaluations = [
            ['squared_error' => 0.25],
            ['squared_error' => 0.09],
            ['squared_error' => 0.02],
        ];

        $mse = $service->calculateMse($evaluations);

        $this->assertEqualsWithDelta(
            0.12,
            $mse,
            0.000001
        );
    }


    public function test_backtest_uses_ratings_before_each_match(): void
    {
        $elo = new EloRatingService();
        $service = new EloBacktestService($elo);

        $teams = [
            ['id' => 'a', 'name' => 'Time A', 'initial_elo' => 1500],
            ['id' => 'b', 'name' => 'Time B', 'initial_elo' => 1500],
            ['id' => 'c', 'name' => 'Time C', 'initial_elo' => 1500],
        ];

        $matches = [
            [
                'id' => 1,
                'played_at' => '2025-03-29',
                'home_team_id' => 'a',
                'away_team_id' => 'b',
                'home_goals' => 2,
                'away_goals' => 1,
            ],
            [
                'id' => 2,
                'played_at' => '2025-03-30',
                'home_team_id' => 'a',
                'away_team_id' => 'c',
                'home_goals' => 0,
                'away_goals' => 1,
            ],
        ];

        $result = $service->backtest(
            $teams,
            $matches,
            20,
            0
        );

        $this->assertSame(2, $result['matches_evaluated']);

        // Na primeira partida, os dois clubes têm Elo 1500.
        $this->assertEqualsWithDelta(
            0.5,
            $result['evaluations'][0]['expected'],
            0.000001
        );

        // Após vencer, o Time A passa de 1500 para 1510.
        // Portanto, sua expectativa na segunda partida aumenta.
        $this->assertGreaterThan(
            0.5,
            $result['evaluations'][1]['expected']
        );

        // Confirma a expectativa da segunda partida.
        $expectedSecond = $elo->calculateExpectedEloScore(
            1510,
            1500,
            0
        );

        $this->assertEqualsWithDelta(
            $expectedSecond,
            $result['evaluations'][1]['expected'],
            0.000001
        );

        // O MSE é a média dos erros das duas partidas.
        $expectedMse = (
            $result['evaluations'][0]['squared_error']
            + $result['evaluations'][1]['squared_error']
        ) / 2;

        $this->assertEqualsWithDelta(
            $expectedMse,
            $result['mse'],
            0.000001
        );

        $this->assertEqualsWithDelta(
            0.25,
            $result['baseline_mse'],
            0.000001
        );

        $this->assertEqualsWithDelta(
            0.25,
            $result['home_baseline_mse'],
            0.000001
        );
    }

    public function test_calculates_baseline_mse(): void
    {
        $service = new EloBacktestService(
            new EloRatingService()
        );

        $evaluations = [
            ['actual' => 1.0],
            ['actual' => 0.5],
            ['actual' => 0.0],
        ];

        $mse = $service->calculateBaselineMse($evaluations);

        // (0.25 + 0 + 0.25) / 3
        $expected = 0.5 / 3;

        $this->assertEqualsWithDelta(
            $expected,
            $mse,
            0.000001
        );
    }

    public function test_calculates_home_advantage_baseline(): void
    {
        $elo = new EloRatingService();

        $service = new EloBacktestService($elo);

        $evaluations = [
            ['actual' => 1.0],
            ['actual' => 1.0],
        ];

        $mse = $service->calculateHomeBaselineMse(
            $evaluations,
            70
        );

        $expected = $elo->calculateExpectedEloScore(
            1500,
            1500,
            70
        );

        $expectedMse = (1 - $expected) ** 2;

        $this->assertEqualsWithDelta(
            $expectedMse,
            $mse,
            0.000001
        );

        // Sem vantagem de mando, a expectativa é 0,5.
        $this->assertEqualsWithDelta(
            0.25,
            $service->calculateHomeBaselineMse(
                $evaluations,
                0
            ),
            0.000001
        );
    }

}
