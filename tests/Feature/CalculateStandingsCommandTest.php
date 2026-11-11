<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculateStandingsCommandTest extends TestCase
{
    public function test_command_reads_json_fixtures_and_prints_table(): void
    {
        $fixtures = base_path('tests/Fixtures');

        $this->artisan('standings:calculate', [
            'teams' => $fixtures.'/standings-teams.json',
            'matches' => $fixtures.'/standings-matches.json',
        ])
            ->expectsOutputToContain('Classificação calculada: 4 equipes e 4 partidas.')
            ->expectsOutputToContain('Aurora FC')
            ->expectsOutputToContain('Atlético do Vale')
            ->assertSuccessful();
    }

    public function test_command_reports_unreadable_input(): void
    {
        $this->artisan('standings:calculate', [
            'teams' => base_path('tests/Fixtures/inexistente.json'),
            'matches' => base_path('tests/Fixtures/standings-matches.json'),
        ])
            ->expectsOutputToContain('Arquivo de equipes não encontrado')
            ->assertFailed();
    }
}
