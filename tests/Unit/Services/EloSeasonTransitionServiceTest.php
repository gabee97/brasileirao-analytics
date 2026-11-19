<?php

namespace Tests\Unit\Services;

use App\Services\EloSeasonTransitionService;
use PHPUnit\Framework\TestCase;

class EloSeasonTransitionServiceTest extends TestCase
{
    public function test_prepares_next_season_using_previous_ratings(): void
    {
        $service = new EloSeasonTransitionService();

        $previousRanking = [
            [
                'id' => 'flamengo',
                'elo' => 1580.0,
            ],
            [
                'id' => 'palmeiras',
                'elo' => 1600.0,
            ],
        ];

        $nextSeasonTeams = [
            [
                'id' => 'flamengo',
                'name' => 'Flamengo',
                'initial_elo' => 1500,
            ],
            [
                'id' => 'mirassol',
                'name' => 'Mirassol',
                'initial_elo' => 1500,
            ],
        ];

        $result = $service->prepareNextSeason(
            $nextSeasonTeams,
            $previousRanking
        );

        // Clube que permaneceu na Série A.
        $this->assertSame(
            1580.0,
            $result[0]['initial_elo']
        );

        // Clube promovido, sem histórico na Série A anterior.
        $this->assertSame(
            1500.0,
            $result[1]['initial_elo']
        );

        // Identificadores devem permanecer iguais.
        $this->assertSame('flamengo', $result[0]['id']);
        $this->assertSame('mirassol', $result[1]['id']);

        // O array original não deve ser modificado.
        $this->assertSame(
            1500,
            $nextSeasonTeams[0]['initial_elo']
        );
    }

    public function test_applies_regression_to_the_mean(): void
    {
        $service = new EloSeasonTransitionService();

        $previousRanking = [
            ['id' => 'a', 'elo' => 1600.0],
            ['id' => 'b', 'elo' => 1400.0],
        ];

        $nextSeasonTeams = [
            ['id' => 'a', 'name' => 'Time A', 'initial_elo' => 1500],
            ['id' => 'b', 'name' => 'Time B', 'initial_elo' => 1500],
            ['id' => 'c', 'name' => 'Time C', 'initial_elo' => 1500],
        ];

        $result = $service->prepareNextSeason(
            $nextSeasonTeams,
            $previousRanking,
            0.75
        );

        // 1500 + 0.75 * (1600 - 1500)
        $this->assertSame(1575.0, $result[0]['initial_elo']);

        // 1500 + 0.75 * (1400 - 1500)
        $this->assertSame(1425.0, $result[1]['initial_elo']);

        // Clube sem histórico anterior.
        $this->assertSame(1500.0, $result[2]['initial_elo']);
    }
}
