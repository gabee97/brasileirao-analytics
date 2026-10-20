<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MatchController extends Controller
{
    public function index(string $season): JsonResponse
    {
        // TODO: definir o contrato de dados e implementar este recurso.
        return response()->json([
            'success' => false,
            'message' => 'Funcionalidade ainda não implementada.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}
