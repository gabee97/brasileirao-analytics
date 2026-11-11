<?php

namespace App\Console\Commands;

use App\Services\StandingsService;
use App\Support\JsonArrayFileReader;
use Illuminate\Console\Command;
use Throwable;

class CalculateStandings extends Command
{
    protected $signature = 'standings:calculate {teams : Caminho para teams.json} {matches : Caminho para matches.json}';

    protected $description = 'Calcula e exibe a classificação a partir dos JSONs normalizados do importador';

    public function handle(StandingsService $service, JsonArrayFileReader $reader): int
    {
        try {
            $teams = $reader->read((string) $this->argument('teams'), 'equipes');
            $matches = $reader->read((string) $this->argument('matches'), 'partidas');
            $ranking = $service->calculate($teams, $matches);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Classificação calculada: %d equipes e %d partidas.', count($teams), count($matches)));
        $this->table(
            ['Pos', 'Equipe', 'P', 'J', 'V', 'E', 'D', 'GP', 'GC', 'SG'],
            array_map(static fn (array $row): array => [
                $row['position'], $row['name'], $row['points'], $row['played'],
                $row['wins'], $row['draws'], $row['losses'], $row['goals_for'],
                $row['goals_against'], $row['goal_difference'],
            ], $ranking),
        );

        return self::SUCCESS;
    }
}
