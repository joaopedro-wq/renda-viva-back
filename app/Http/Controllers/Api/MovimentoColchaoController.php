<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovimentoColchaoResource;
use App\Models\MovimentoColchao;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MovimentoColchaoController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimentoColchao::doUsuario($request->user()->id);

        if ($mes = $request->query('mes')) {
            $data = \DateTime::createFromFormat('Y-m', $mes);

            if (! $data) {
                throw ValidationException::withMessages(['mes' => 'Formato esperado: YYYY-MM.']);
            }

            $query->whereYear('data', $data->format('Y'))->whereMonth('data', $data->format('m'));
        }

        $movimentos = $query->orderByDesc('data')->get();

        return MovimentoColchaoResource::collection($movimentos);
    }
}
