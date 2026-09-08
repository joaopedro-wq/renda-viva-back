<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModeloImportacaoResource;
use App\Models\ModeloImportacao;
use Illuminate\Http\Request;

/**
 * Só leitura — modelos são criados internamente pelo ImportacaoController
 * quando o usuário mapeia um formato novo. Serve pra UI oferecer "usar um
 * modelo salvo" se o auto-match pela assinatura do cabeçalho falhar.
 */
class ModeloImportacaoController extends Controller
{
    public function index(Request $request)
    {
        $modelos = ModeloImportacao::doUsuario($request->user()->id)->orderBy('nome')->get();

        return ModeloImportacaoResource::collection($modelos);
    }
}
