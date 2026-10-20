# Testes unitários dos serviços

Esta pasta está reservada para testes matemáticos em PHPUnit, sem inicializar Laravel
(use `PHPUnit\Framework\TestCase`). Nenhuma fórmula ou saída foi presumida nesta etapa.

Ao implementar cada serviço, crie seu arquivo correspondente:

- `EloRatingServiceTest.php`: parâmetros, influência dos adversários, mando e atualização após partidas.
- `PoissonPredictionServiceTest.php`: gols esperados, placares e agregação dos resultados.
- `MonteCarloSimulatorTest.php`: controle de aleatoriedade, repetibilidade e consolidação.
- `StandingsServiceTest.php`: pontuação e desempates conforme regulamento definido.
- `ProjectionServiceTest.php`: agregação das simulações e regras das vagas continentais.

Defina os contratos e os resultados esperados antes dos cálculos. As fixtures podem ser
carregadas com `require dirname(__DIR__, 2).'/Fixtures/teams.php'` e `matches.php`.
Os valores fictícios servem apenas como entradas; não estabelecem previsões esperadas.
