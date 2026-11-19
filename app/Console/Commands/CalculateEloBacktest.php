<?php

namespace App\Console\Commands;

use App\Services\EloBacktestService;
use App\Services\EloRatingService;
use App\Services\EloSeasonTransitionService;
use App\Support\JsonArrayFileReader;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class CalculateEloBacktest extends Command
{
    protected $signature = 'elo:backtest
        {teams : Caminho para teams.json}
        {matches : Caminho para matches.json}
        {--k=20 : Fator de atualização do Elo}
        {--home-advantage=70 : Vantagem do mandante}
        {--previous-teams= : Caminho para teams.json da temporada anterior}
        {--previous-matches= : Caminho para matches.json da temporada anterior}
        {--retention=1 : Proporção do Elo histórico preservada, de 0 a 1}';

    protected $description = 'Avalia o desempenho preditivo do Elo usando partidas históricas';

    public function handle(
        EloBacktestService $service,
        JsonArrayFileReader $reader,
        EloRatingService $eloService,
        EloSeasonTransitionService $transitionService
    ): int {
        try {
            $k = $this->numericOption('k', true);
            $homeAdvantage = $this->numericOption('home-advantage');

            $retention = $this->numericOption('retention');

            if ($retention < 0 || $retention > 1) {
                throw new InvalidArgumentException(
                    'A opção --retention deve estar entre 0 e 1.'
                );
            }

            // 1. Primeiro, validamos os parâmetros históricos.
            $previousTeamsPath = $this->option('previous-teams');
            $previousMatchesPath = $this->option('previous-matches');

            $hasPreviousTeams = is_string($previousTeamsPath)
                && trim($previousTeamsPath) !== '';

            $hasPreviousMatches = is_string($previousMatchesPath)
                && trim($previousMatchesPath) !== '';

            if ($hasPreviousTeams !== $hasPreviousMatches) {
                throw new InvalidArgumentException(
                    'Informe --previous-teams e --previous-matches juntos.'
                );
            }

            // 2. Somente depois lemos os arquivos.
            $teams = $reader->read(
                (string) $this->argument('teams'),
                'equipes'
            );

            $matches = $reader->read(
                (string) $this->argument('matches'),
                'partidas'
            );

            // 3. Se houver temporada anterior, calculamos seu Elo.
            if ($hasPreviousTeams) {
                $previousTeams = $reader->read(
                    $previousTeamsPath,
                    'equipes anteriores'
                );

                $previousMatches = $reader->read(
                    $previousMatchesPath,
                    'partidas anteriores'
                );

                $previousResult = $eloService->calculate(
                    $previousTeams,
                    $previousMatches,
                    [
                        'k' => $k,
                        'home_advantage' => $homeAdvantage,
                    ]
                );

                $teams = $transitionService->prepareNextSeason(
                    $teams,
                    $previousResult['ranking'],
                    $retention
                );
            }

            // 4. Executamos o backtesting.
            $result = $service->backtest(
                $teams,
                $matches,
                $k,
                $homeAdvantage
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Backtesting Elo concluído!');

        $this->table(
            ['Indicador', 'Resultado'],
            [
                ['Partidas avaliadas', $result['matches_evaluated']],
                ['Fator K', $k],
                ['Vantagem de mando', $homeAdvantage],
                ['Retenção histórica',
                    $hasPreviousTeams
                        ? number_format($retention * 100, 0, ',', '.') . '%'
                        : 'Não aplicada',
                ],
                ['MSE Elo', number_format($result['mse'], 6, ',', '.')],
                ['MSE baseline', number_format($result['baseline_mse'], 6, ',', '.')],
                [
                    'MSE baseline (mando)',
                    number_format($result['home_baseline_mse'], 6, ',', '.'),
                ],
                [
                    'Melhoria sobre baseline',
                    $result['baseline_mse'] > 0
                        ? number_format(
                            (1 - $result['mse'] / $result['baseline_mse']) * 100,
                            2,
                            ',',
                            '.'
                        ) . '%'
                        : 'N/A',
                ],
                [
                    'Melhoria sobre mando',
                    $result['home_baseline_mse'] > 0
                        ? number_format(
                            (
                                1 - $result['mse'] /
                                $result['home_baseline_mse']
                            ) * 100,
                            2,
                            ',',
                            '.'
                        ) . '%'
                        : 'N/A',
                ],
            ]
        );

        $this->newLine();
        $this->line(
            'Quanto menor o MSE, menor o erro quadrático médio.'
        );

        return self::SUCCESS;
    }

    private function numericOption(
        string $name,
        bool $positive = false
    ): float {
        $value = $this->option($name);

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new InvalidArgumentException(
                "A opção --{$name} deve ser numérica."
            );
        }

        $number = (float) $value;

        if (!is_finite($number) || ($positive && $number <= 0)) {
            throw new InvalidArgumentException(
                "A opção --{$name} possui valor inválido."
            );
        }

        return $number;
    }
}
