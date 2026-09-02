<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SafeToSpendService;
use Illuminate\Http\Request;

/**
 * Controller fino — toda a regra do cálculo mora em SafeToSpendService.
 */
class PainelController extends Controller
{
    public function __construct(private readonly SafeToSpendService $safeToSpendService) {}

    public function dadoDaSemana(Request $request)
    {
        $resultado = $this->safeToSpendService->calcular($request->user());

        return response()->json(['data' => $resultado->toArray()]);
    }
}
