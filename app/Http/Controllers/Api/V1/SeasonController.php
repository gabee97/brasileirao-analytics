<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SeasonController extends Controller
{
    public function index(): JsonResponse
    {
        // TODO: definir o contrato de dados e implementar este recurso.
        return response()->json([
            'success' => false,
            'message' => 'Funcionalidade ainda não implementada.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}
