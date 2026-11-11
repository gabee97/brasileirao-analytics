<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculateEloCommandTest extends TestCase
{
    public function test_command_calculates_ranking_from_json_fixtures(): void
    {
        $fixtures = base_path('tests/Fixtures');

        $this->artisan('elo:calculate', [
            'teams' => $fixtures.'/standings-teams.json',
            'matches' => $fixtures.'/standings-matches.json',
            '--k' => '30',
            '--home-advantage' => '50',
        ])
            ->expectsOutputToContain('Ranking Elo calculado: 4 equipes, 4 partidas, K=30, vantagem de mando=50.')
            ->expectsOutputToContain('Elo inicial')
            ->expectsOutputToContain('Aurora FC')
            ->expectsOutputToContain('medida relativa de força')
            ->assertSuccessful();
    }

    public function test_command_rejects_invalid_parameter(): void
    {
        $fixtures = base_path('tests/Fixtures');

        $this->artisan('elo:calculate', [
            'teams' => $fixtures.'/standings-teams.json',
            'matches' => $fixtures.'/standings-matches.json',
            '--k' => '0',
        ])
            ->expectsOutputToContain('A opção --k deve ser positiva e finita.')
            ->assertFailed();
    }

    public function test_command_rejects_invalid_json(): void
    {
        $fixtures = base_path('tests/Fixtures');

        $this->artisan('elo:calculate', [
            'teams' => $fixtures.'/invalid.json',
            'matches' => $fixtures.'/standings-matches.json',
        ])
            ->expectsOutputToContain('JSON de equipes inválido')
            ->assertFailed();
    }
}
