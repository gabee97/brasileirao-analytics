<?php

namespace App\Console\Commands;

use App\Services\EloBacktestService;
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
        {--home-advantage=70 : Vantagem do mandante}';

    protected $description = 'Avalia o desempenho preditivo do Elo usando partidas históricas';

    public function handle(
        EloBacktestService $service,
        JsonArrayFileReader $reader
    ): int {
        try {
            $k = $this->numericOption('k', true);
            $homeAdvantage = $this->numericOption('home-advantage');

            $teams = $reader->read(
                (string) $this->argument('teams'),
                'equipes'
            );

            $matches = $reader->read(
                (string) $this->argument('matches'),
                'partidas'
            );

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
