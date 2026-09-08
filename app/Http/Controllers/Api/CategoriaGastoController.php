<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaGastoResource;
use App\Models\CategoriaGasto;

class CategoriaGastoController extends Controller
{
    public function index()
    {
        return CategoriaGastoResource::collection(CategoriaGasto::orderBy('nome')->get());
    }
}
