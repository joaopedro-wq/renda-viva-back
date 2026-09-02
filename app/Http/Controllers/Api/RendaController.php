<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRendaRequest;
use App\Http\Requests\UpdateRendaRequest;
use App\Http\Resources\RendaResource;
use App\Models\Renda;
use Illuminate\Http\Request;

class RendaController extends Controller
{
    public function index(Request $request)
    {
        $rendas = Renda::doUsuario($request->user()->id)
            ->orderByDesc('data_recebimento')
            ->get();

        return RendaResource::collection($rendas);
    }

    public function store(StoreRendaRequest $request)
    {
        $renda = $request->user()->rendas()->create($request->validated());

        return RendaResource::make($renda)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Renda $renda)
    {
        $this->autorizarDono($request, $renda);

        return RendaResource::make($renda);
    }

    public function update(UpdateRendaRequest $request, Renda $renda)
    {
        $this->autorizarDono($request, $renda);

        $renda->update($request->validated());

        return RendaResource::make($renda);
    }

    public function destroy(Request $request, Renda $renda)
    {
        $this->autorizarDono($request, $renda);

        $renda->delete();

        return response()->json(null, 204);
    }

    private function autorizarDono(Request $request, Renda $renda): void
    {
        abort_if($renda->usuario_id !== $request->user()->id, 404);
    }
}
