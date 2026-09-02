<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGastoRequest;
use App\Http\Requests\UpdateGastoRequest;
use App\Http\Resources\GastoResource;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class GastoController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Gasto::doUsuario($request->user()->id);

        if ($mes = $request->query('mes')) {
            $data = \DateTime::createFromFormat('Y-m', $mes);

            if (! $data) {
                throw ValidationException::withMessages(['mes' => 'Formato esperado: YYYY-MM.']);
            }

            $query->whereYear('data', $data->format('Y'))->whereMonth('data', $data->format('m'));
        }

        $gastos = $query->orderByDesc('data')->get();

        return GastoResource::collection($gastos);
    }

    public function store(StoreGastoRequest $request)
    {
        $gasto = $request->user()->gastos()->create($request->validated());

        return GastoResource::make($gasto)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Gasto $gasto)
    {
        $this->autorizarDono($request, $gasto);

        return GastoResource::make($gasto);
    }

    public function update(UpdateGastoRequest $request, Gasto $gasto)
    {
        $this->autorizarDono($request, $gasto);

        $gasto->update($request->validated());

        return GastoResource::make($gasto);
    }

    public function destroy(Request $request, Gasto $gasto)
    {
        $this->autorizarDono($request, $gasto);

        $gasto->delete();

        return response()->json(null, 204);
    }

  
    private function autorizarDono(Request $request, Gasto $gasto): void
    {
        abort_if($gasto->usuario_id !== $request->user()->id, 404);
    }
}
