<?php

namespace App\Services;

use BadMethodCallException;

class ProjectionService
{
    /**
     * Consolidar projeções a partir dos resultados das simulações.
     * TODO: definir o contrato dos resultados e as regras da temporada.
     * TODO: consolidar probabilidades de título, rebaixamento e de cada posição.
     * TODO: aplicar as regras de vagas para Libertadores e Sul-Americana.
     *
     * Os contratos de arrays são provisórios; definir com os primeiros testes matemáticos.
     * Nenhum cálculo é executado nesta etapa.
     *
     * @throws BadMethodCallException
     */
    public function project(array $simulationResults, array $rules = []): array
    {
        throw new BadMethodCallException('Funcionalidade ainda não implementada.');
    }
}
