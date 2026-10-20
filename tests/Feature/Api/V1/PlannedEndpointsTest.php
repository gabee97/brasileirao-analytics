<?php

namespace Tests\Feature\Api\V1;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlannedEndpointsTest extends TestCase
{
    public static function endpoints(): array
    {
        return [
            'seasons' => ['api.v1.seasons.index', [], '/api/v1/seasons'],
            'teams' => ['api.v1.seasons.teams.index', ['season' => 2027], '/api/v1/seasons/2027/teams'],
            'matches' => ['api.v1.seasons.matches.index', ['season' => 2027], '/api/v1/seasons/2027/matches'],
            'standings' => ['api.v1.seasons.standings.show', ['season' => 2027], '/api/v1/seasons/2027/standings'],
            'ratings' => ['api.v1.seasons.ratings.index', ['season' => 2027], '/api/v1/seasons/2027/ratings'],
            'prediction' => ['api.v1.matches.prediction.show', ['match' => 1], '/api/v1/matches/1/prediction'],
            'projections' => ['api.v1.seasons.projections.show', ['season' => 2027], '/api/v1/seasons/2027/projections'],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_planned_endpoint_is_registered_and_not_implemented(string $name, array $parameters, string $uri): void
    {
        $this->assertSame($uri, route($name, $parameters, false));

        // Também exige JSON sem depender do cabeçalho Accept do cliente.
        $this->get($uri)
            ->assertStatus(501)
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'success' => false,
                'message' => 'Funcionalidade ainda não implementada.',
            ]);
    }
}
