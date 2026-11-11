# Brasileirão Analytics

Base de uma aplicação para estudar e calcular probabilidades do Campeonato Brasileiro
Série A. A primeira temporada-alvo é o **Brasileirão 2027**.

O projeto começa como portfólio e laboratório pessoal de PHP, Laravel e matemática.
No futuro, poderá oferecer um site público para torcedores acompanharem a força dos
clubes e as probabilidades do campeonato. O objetivo desta etapa é deixar uma base
simples para um único desenvolvedor implementar e compreender os algoritmos.

## Status atual

**Esqueleto inicial; modelos estatísticos ainda não implementados.**

Implementado:

- Página inicial Blade em português, com aviso de desenvolvimento.
- API REST `/api/v1`, com sete endpoints nomeados e controllers específicos.
- Resposta JSON uniforme com HTTP 501 em todos os endpoints planejados.
- Cinco serviços com métodos provisórios, responsabilidades e TODOs.
- Fixtures isoladas com quatro clubes fictícios, Elo inicial e quatro partidas.
- Testes de inicialização, página inicial e contrato das rotas.
- Configuração preparada para PostgreSQL, sem conexão necessária nesta etapa.

Planejado: cálculos Elo, Poisson, classificação, simulações Monte Carlo, projeções,
persistência, dashboard e atualização de resultados. Não há autenticação, integração
externa, modelos de IA, tabelas ou migrations nesta versão. Os métodos dos serviços
lançam `BadMethodCallException` até serem implementados; não produzem probabilidades.

## Tecnologias

- PHP 8.3 ou superior compatível com as dependências travadas.
- Laravel 13 (versões exatas no `composer.lock`).
- PHPUnit 12 para testes e Laravel Pint para formatação PSR-12.
- Blade e CSS simples, sem necessidade de Node, Vite ou compilação de frontend.
- PostgreSQL planejado para persistência.
- Redis e Laravel Queues planejados para simulações assíncronas.

Por enquanto: sessão e cache em arquivos, fila `sync`. Redis não é requisito para
iniciar. O `.env.example` indica PostgreSQL como banco futuro; nenhum endpoint faz
consultas. O esqueleto dispensa as migrations e o modelo User padrão do Laravel.

## Modelos previstos

### Elo

Representa a força relativa de uma equipe por uma pontuação, atualizada após partidas
levando em conta a força do adversário. Mando de campo e parâmetros de atualização
serão definidos e validados pelo desenvolvedor. Uma pontuação Elo isolada não é uma
probabilidade pronta de título.

### Poisson

Modelo probabilístico para contagens, usado aqui futuramente para representar gols
com base em valores esperados por equipe. A distribuição dos placares permitirá
estimar vitória, empate e derrota. A estimativa dos parâmetros, as hipóteses do modelo
e suas limitações deverão ser definidas e testadas antes do uso público.

### Monte Carlo

Repetirá simulações dos jogos restantes para obter classificações possíveis e
consolidar a frequência dos desfechos. Dependerá de um modelo de partidas e dos
critérios de classificação. Quantidade de simulações, reprodutibilidade e avaliação
da incerteza ainda serão definidas.

Nenhuma fórmula foi implementada. Antes da estreia, a ideia é usar **temporadas
anteriores do Brasileirão** para validar os algoritmos: alimentar o modelo apenas
com informações disponíveis até cada data e comparar previsões com os resultados
posteriores, evitando usar informações futuras na previsão histórica.

## Estrutura

```text
app/
├── Http/Controllers/Api/V1/
│   ├── SeasonController.php
│   ├── TeamController.php
│   ├── MatchController.php
│   ├── StandingsController.php
│   ├── RatingController.php
│   ├── PredictionController.php
│   └── ProjectionController.php
├── Providers/AppServiceProvider.php
└── Services/
    ├── EloRatingService.php
    ├── PoissonPredictionService.php
    ├── MonteCarloSimulator.php
    ├── StandingsService.php
    └── ProjectionService.php
bootstrap/app.php
config/
resources/views/welcome.blade.php
routes/
├── api.php
├── web.php
└── console.php
tests/
├── Feature/
│   ├── ApplicationTest.php
│   └── Api/V1/PlannedEndpointsTest.php
├── Fixtures/
│   ├── teams.php
│   └── matches.php
└── Unit/Services/README.md
```

Controllers cuidarão da entrada e resposta HTTP. Serviços concentrarão a lógica de
negócio sem depender de HTTP ou de fixtures. Os arrays são contratos provisórios,
a serem detalhados junto dos primeiros testes, sem interfaces ou repositórios nesta
fase. No futuro, os dados poderão vir do banco sem transformar os serviços em
controllers ou acoplá-los aos arquivos de teste.

| Serviço | Responsabilidade futura |
| --- | --- |
| `EloRatingService::calculate` | Força dos clubes, mando, adversários, atualização por partida e parâmetros |
| `PoissonPredictionService::predict` | Gols esperados, placares, vitória, empate e derrota |
| `MonteCarloSimulator::simulate` | Jogos restantes, milhares de execuções e consolidação de resultados |
| `StandingsService::calculate` | Pontos, vitórias, saldo e desempates, inclusive nas simulações |
| `ProjectionService::project` | Título, rebaixamento, vagas continentais e distribuição por posição |

## Instalação e configuração

Requisitos: PHP 8.3+, Composer 2 e extensões requeridas pelo Laravel/PHPUnit, incluindo
DOM, XML, mbstring, cURL e PDO. Para a futura persistência, habilite `pdo_pgsql`.

Na pasta do projeto:

```bash
composer install
cp .env.example .env
php artisan key:generate
composer check-platform-reqs
```

Não substitua um `.env` já configurado. Nesta cópia de desenvolvimento, ele já foi
criado e a chave gerada. O arquivo não deve ser versionado. O `composer.lock` deve
ser versionado para instalações reproduzíveis. Alternativamente, `composer run setup`
instala dependências, cria `.env` se ausente e gera a chave; não executa migrations.

As variáveis de banco planejadas são:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=brasileirao_analytics
DB_USERNAME=postgres
DB_PASSWORD=
```

Ajuste credenciais apenas quando implementar a persistência. Não é necessário criar
banco ou executar `artisan migrate` agora. Mantenha `SESSION_DRIVER=file`,
`CACHE_STORE=file` e `QUEUE_CONNECTION=sync` para usar a base sem serviços externos.

## Servidor local

```bash
cd /var/www/html/brasileirao-analytics
php artisan serve --host=127.0.0.1 --port=8000
```

Abra <http://127.0.0.1:8000>. Também é possível usar `composer run dev`.
Não há etapa npm. Se configurar Apache/Nginx futuramente, a raiz pública deve ser
`public/`, nunca a raiz inteira do projeto.

## Calcular a classificação

Use os arquivos JSON normalizados pelo importador sem copiá-los para este projeto:

```bash
php artisan standings:calculate \
  ../brasileirao-importer/data/normalized/teams.json \
  ../brasileirao-importer/data/normalized/matches.json
```

O comando exibe uma tabela com posição, equipe, pontos (P), jogos (J), vitórias (V),
empates (E), derrotas (D), gols pró (GP), gols contra (GC) e saldo (SG). Os critérios
de desempate disponíveis nos dados seguem o art. 15 do regulamento da Série A 2025:
vitórias, saldo de gols, gols pró e confronto direto. Este último só é aplicado entre
dois clubes, quando os dois jogos de ida e volta estão presentes. Cartões e sorteio
não são aplicados porque não fazem parte do contrato exportado.

Os dois argumentos são obrigatórios. Arquivos ausentes ou ilegíveis, JSON malformado,
raiz que não seja uma lista, equipes duplicadas, referências a equipes desconhecidas
e gols ausentes, negativos ou não inteiros produzem uma mensagem de erro e código de
saída `1`. A leitura não altera os arquivos do importador e não usa banco de dados.

## Calcular o ranking Elo

Para processar a temporada com os parâmetros padrão (`K=20` e vantagem de mando
de 70 pontos Elo):

```bash
php artisan elo:calculate \
  ../brasileirao-importer/data/normalized/teams.json \
  ../brasileirao-importer/data/normalized/matches.json
```

Os parâmetros podem ser ajustados explicitamente:

```bash
php artisan elo:calculate teams.json matches.json --k=30 --home-advantage=50
```

A tabela apresenta posição, clube, Elo inicial, Elo final e variação, ordenada pelo
Elo final. Nesta etapa todas as equipes devem possuir `initial_elo` igual a 1500.
O Elo é uma medida relativa de força e não deve ser interpretado como probabilidade
de vitória, título ou rebaixamento.

Os arquivos são validados antes do cálculo. O comando rejeita arquivos ausentes,
JSON inválido, campos obrigatórios ausentes, equipes ou partidas duplicadas, placares
inválidos, ratings não finitos e parâmetros inválidos. Falhas retornam código `1`.

As partidas são ordenadas por `played_at` e, em datas iguais, pelo `id` original.
Isso torna o resultado reproduzível para a fonte de 2025, que contém somente datas,
mas não significa que essa tenha sido a ordem cronológica real dos jogos no mesmo dia.

## Testes e estilo

```bash
composer test
vendor/bin/pint --preset psr12 --test
```

Os testes verificam a inicialização, a view inicial e as sete rotas (nome, URL,
HTTP 501, Content-Type JSON e corpo exato), sem banco e sem resultados matemáticos
presumidos. A suíte Unit já está configurada; `tests/Unit/Services/README.md` orienta
os próximos testes. Não há testes matemáticos executáveis enquanto os contratos e
fórmulas não forem definidos. Para formatar: `vendor/bin/pint --preset psr12`.

Verificação desta implementação: `composer test` passou com **9 testes e 42
asserções**; `composer validate --strict`, `composer check-platform-reqs` e
`vendor/bin/pint --test` também passaram. Todos os testes solicitados foram
executados, sem depender de banco. O script de testes foi ajustado para o Composer
2.7.1 disponível, sem a opção de scripts `@no_additional_args` do esqueleto original.

### Fixtures

`tests/Fixtures/teams.php` contém quatro clubes inventados, identificadores e
`initial_elo`. `tests/Fixtures/matches.php` contém identificador, mandante, visitante,
gols e data ISO 8601 com fuso. Os identificadores das equipes ligam os dois arrays.
As datas de exemplo não representam calendário oficial e os valores Elo não foram
calibrados. Esses arquivos não são carregados por rotas públicas.

Em um teste de `tests/Unit/Services`, carregue os arrays com:

```php
$teams = require dirname(__DIR__, 2).'/Fixtures/teams.php';
$matches = require dirname(__DIR__, 2).'/Fixtures/matches.php';
```

Comece por `app/Services/EloRatingService.php` e defina o contrato e os testes em
`tests/Unit/Services/EloRatingServiceTest.php` antes de desenvolver os cálculos.

## API REST v1

Todos os endpoints abaixo aceitam **GET**, não exigem autenticação e retornam
**HTTP 501 Not Implemented** nesta etapa. `{season}` e `{match}` são parâmetros
simples, sem model binding ou consulta ao banco.

| Endpoint | Controller / método | Recurso futuro |
| --- | --- | --- |
| `/api/v1/seasons` | `SeasonController::index` | Temporadas |
| `/api/v1/seasons/{season}/teams` | `TeamController::index` | Equipes da temporada |
| `/api/v1/seasons/{season}/matches` | `MatchController::index` | Partidas da temporada |
| `/api/v1/seasons/{season}/standings` | `StandingsController::show` | Classificação |
| `/api/v1/seasons/{season}/ratings` | `RatingController::index` | Força Elo |
| `/api/v1/matches/{match}/prediction` | `PredictionController::show` | Previsão da partida |
| `/api/v1/seasons/{season}/projections` | `ProjectionController::show` | Título, rebaixamento e vagas continentais |

Resposta atual:

```json
{
  "success": false,
  "message": "Funcionalidade ainda não implementada."
}
```

A rota web `GET /` retorna HTTP 200 com a apresentação do projeto. A rota padrão
de saúde do Laravel `GET /up` verifica a inicialização. Liste as rotas com
`php artisan route:list`.

## Roadmap

- [ ] Implementar motor Elo.
- [ ] Criar testes matemáticos do Elo.
- [ ] Processar partidas utilizando dados fixos.
- [ ] Importar resultados históricos.
- [ ] Modelar e integrar o banco PostgreSQL.
- [ ] Implementar cálculo de classificação.
- [ ] Implementar modelo de Poisson.
- [ ] Implementar simulação Monte Carlo.
- [ ] Calcular probabilidades de título e rebaixamento.
- [ ] Implementar regras de classificação continental.
- [ ] Validar previsões com temporadas anteriores.
- [ ] Desenvolver dashboard estatístico.
- [ ] Automatizar atualização dos resultados.
- [ ] Preparar lançamento para o Brasileirão 2027.

As regras de desempate e vagas continentais serão implementadas a partir do
regulamento da temporada correspondente, sem presumir regras de 2027 nesta etapa.

## Ambiente e limitações

A inspeção inicial encontrou PHP 8.3.6, Composer 2.7.1 e `pdo_pgsql`. Outros projetos
Laravel existentes no diretório pai foram preservados. O download via Composer
precisou de autorização de rede porque o sandbox não resolvia o DNS do Packagist.
PostgreSQL e Redis não foram conectados ou validados; não são necessários nesta fase.
Nenhuma migration foi executada e nenhum commit foi criado.
