<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GastoController;
use App\Http\Controllers\Api\ObrigacaoFixaController;
use App\Http\Controllers\Api\PainelController;
use App\Http\Controllers\Api\RendaController;
use Illuminate\Support\Facades\Route;

// Público — fora de auth:sanctum. Bearer token puro, nunca cookie/CSRF.
Route::post('/login', [AuthController::class, 'login']);
Route::post('/criar-usuario', [AuthController::class, 'storeUser']);

// Protegido por auth:sanctum — header Authorization: Bearer <token>.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/get-with-token', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('rendas', RendaController::class);
    Route::apiResource('gastos', GastoController::class);
    Route::apiResource('obrigacoes-fixas', ObrigacaoFixaController::class);

    Route::get('/painel/dado-da-semana', [PainelController::class, 'dadoDaSemana']);
});
