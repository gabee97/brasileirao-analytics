<?php

namespace App\Console\Commands;

use App\Services\EloRatingService;
use App\Support\JsonArrayFileReader;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class CalculateElo extends Command
{
    protected $signature = 'elo:calculate
        {teams : Caminho para teams.json}
        {matches : Caminho para matches.json}
        {--k=20 : Fator de atualização positivo}
        {--home-advantage=70 : Vantagem Elo do mandante}';

    protected $description = 'Calcula e exibe o ranking Elo a partir dos JSONs normalizados do importador';

    public function handle(EloRatingService $service, JsonArrayFileReader $reader): int
    {
        try {
            $k = $this->numericOption('k', positive: true);
            $homeAdvantage = $this->numericOption('home-advantage');
            $teams = $reader->read((string) $this->argument('teams'), 'equipes');
            $matches = $reader->read((string) $this->argument('matches'), 'partidas');
            $result = $service->calculate($teams, $matches, [
                'k' => $k,
                'home_advantage' => $homeAdvantage,
            ]);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Ranking Elo calculado: %d equipes, %d partidas, K=%s, vantagem de mando=%s.',
            count($teams),
            count($matches),
            $this->formatNumber($k),
            $this->formatNumber($homeAdvantage),
        ));
        $this->table(
            ['Pos', 'Clube', 'Elo inicial', 'Elo final', 'Variação'],
            array_map(fn (array $team, int $index): array => [
                $index + 1,
                $team['name'],
                number_format($team['initial_elo'], 2, ',', '.'),
                number_format($team['elo'], 2, ',', '.'),
                sprintf('%+.2f', $team['variation']),
            ], $result['ranking'], array_keys($result['ranking'])),
        );
        $this->newLine();
        $this->line('Elo é uma medida relativa de força; não representa probabilidade de vitória, título ou rebaixamento.');

        return self::SUCCESS;
    }

    private function numericOption(string $name, bool $positive = false): float
    {
        $value = $this->option($name);
        if (! is_scalar($value) || ! is_numeric((string) $value)) {
            throw new InvalidArgumentException("A opção --{$name} deve ser numérica.");
        }

        $number = (float) $value;
        if (! is_finite($number) || ($positive && $number <= 0)) {
            $condition = $positive ? 'positiva e finita' : 'finita';
            throw new InvalidArgumentException("A opção --{$name} deve ser {$condition}.");
        }

        return $number;
    }

    private function formatNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
