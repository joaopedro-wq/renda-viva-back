<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObrigacaoFixaRequest;
use App\Http\Requests\UpdateObrigacaoFixaRequest;
use App\Http\Resources\ObrigacaoFixaResource;
use App\Models\ObrigacaoFixa;
use Illuminate\Http\Request;

/**
 * CRUD de obrigações fixas — sempre escopado por
 * ObrigacaoFixa::doUsuario(auth()->id()). Controller fino, sem regra de
 * negócio aqui (ver CLAUDE.md).
 */
class ObrigacaoFixaController extends Controller
{
    public function index(Request $request)
    {
        $obrigacoes = ObrigacaoFixa::doUsuario($request->user()->id)
            ->orderBy('dia_vencimento')
            ->get();

        return ObrigacaoFixaResource::collection($obrigacoes);
    }

    public function store(StoreObrigacaoFixaRequest $request)
    {
        $obrigacao = $request->user()->obrigacoesFixas()->create($request->validated());

        return ObrigacaoFixaResource::make($obrigacao)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, ObrigacaoFixa $obrigacoes_fixa)
    {
        $this->autorizarDono($request, $obrigacoes_fixa);

        return ObrigacaoFixaResource::make($obrigacoes_fixa);
    }

    public function update(UpdateObrigacaoFixaRequest $request, ObrigacaoFixa $obrigacoes_fixa)
    {
        $this->autorizarDono($request, $obrigacoes_fixa);

        $obrigacoes_fixa->update($request->validated());

        return ObrigacaoFixaResource::make($obrigacoes_fixa);
    }

    public function destroy(Request $request, ObrigacaoFixa $obrigacoes_fixa)
    {
        $this->autorizarDono($request, $obrigacoes_fixa);

        $obrigacoes_fixa->delete();

        return response()->json(null, 204);
    }

    /** Garante que a obrigação pertence ao usuário autenticado — 404 pra não vazar existência. */
    private function autorizarDono(Request $request, ObrigacaoFixa $obrigacao): void
    {
        abort_if($obrigacao->usuario_id !== $request->user()->id, 404);
    }
}
