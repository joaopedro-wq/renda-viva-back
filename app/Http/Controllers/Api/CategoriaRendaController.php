<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaRendaResource;
use App\Models\CategoriaRenda;

/** Catálogo global — só leitura, mesmo padrão de CategoriaGastoController. */
class CategoriaRendaController extends Controller
{
    public function index()
    {
        return CategoriaRendaResource::collection(CategoriaRenda::orderBy('nome')->get());
    }
}
