<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaGastoController;
use App\Http\Controllers\Api\CategoriaRendaController;
use App\Http\Controllers\Api\GastoController;
use App\Http\Controllers\Api\ImportacaoController;
use App\Http\Controllers\Api\ModeloImportacaoController;
use App\Http\Controllers\Api\MovimentoColchaoController;
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
    Route::get('/categorias-gasto', [CategoriaGastoController::class, 'index']);
    Route::get('/categorias-renda', [CategoriaRendaController::class, 'index']);
    Route::get('/movimentos-colchao', [MovimentoColchaoController::class, 'index']);
    Route::get('/modelos-importacao', [ModeloImportacaoController::class, 'index']);

    Route::post('/importacoes/pre-visualizar', [ImportacaoController::class, 'preVisualizar']);
    Route::post('/importacoes/upload-chunk', [ImportacaoController::class, 'uploadChunk']);
    Route::post('/importacoes/confirmar', [ImportacaoController::class, 'confirmar']);

    Route::get('/painel/dado-da-semana', [PainelController::class, 'dadoDaSemana']);
});
