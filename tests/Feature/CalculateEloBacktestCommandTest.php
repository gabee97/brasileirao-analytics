<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculateEloBacktestCommandTest extends TestCase
{
    public function test_requires_both_previous_season_files(): void
    {
        $this->artisan('elo:backtest', [
            'teams' => 'teams.json',
            'matches' => 'matches.json',
            '--previous-teams' => 'previous-teams.json',
        ])
            ->expectsOutputToContain(
                'Informe --previous-teams e --previous-matches juntos.'
            )
            ->assertExitCode(1);
    }

    public function test_rejects_invalid_retention(): void
    {
        $this->artisan('elo:backtest', [
            'teams' => 'teams.json',
            'matches' => 'matches.json',
            '--retention' => '1.5',
        ])
            ->expectsOutputToContain(
                'A opção --retention deve estar entre 0 e 1.'
            )
            ->assertExitCode(1);
    }
}
