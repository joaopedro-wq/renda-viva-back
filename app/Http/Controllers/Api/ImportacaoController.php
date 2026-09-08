<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmarImportacaoRequest;
use App\Http\Requests\PreVisualizarImportacaoRequest;
use App\Http\Requests\UploadChunkImportacaoRequest;
use App\Models\Gasto;
use App\Models\Renda;
use App\Services\ImportacaoPreVisualizacaoService;
use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;


class ImportacaoController extends Controller
{
    public function preVisualizar(PreVisualizarImportacaoRequest $request, ImportacaoPreVisualizacaoService $service)
    {
        return response()->json($service->processar(
            $request->user(),
            $request->file('arquivo')->get(),
            $request->filled('modelo_importacao_id') ? $request->integer('modelo_importacao_id') : null,
            $this->mapeamentoDaRequisicao($request),
        ));
    }

  
    public function uploadChunk(UploadChunkImportacaoRequest $request, ImportacaoPreVisualizacaoService $service)
    {
        $receiver = new FileReceiver('arquivo', $request, HandlerFactory::classFromRequest($request));

        if (! $receiver->isUploaded()) {
            return response()->json(['message' => 'Nenhum arquivo enviado.'], 400);
        }

        $save = $receiver->receive();

        if (! $save->isFinished()) {
            return response()->json(['recebido' => true]);
        }

        $arquivoFinal = $save->getFile();
        $conteudo = $arquivoFinal->get();

   
        @unlink($arquivoFinal->getPathname());

        return response()->json($service->processar(
            $request->user(),
            $conteudo,
            $request->filled('modelo_importacao_id') ? $request->integer('modelo_importacao_id') : null,
            $this->mapeamentoDaRequisicao($request),
        ));
    }

    public function confirmar(ConfirmarImportacaoRequest $request)
    {
        $usuario = $request->user();

        $rendasCriadas = 0;
        $gastosCriados = 0;
        $jaExistiam = 0;

        foreach ($request->validated('linhas') as $linha) {
            $origemExternaId = $linha['identificador_externo'] ?? null;
            $modelClass = $linha['tipo'] === 'renda' ? Renda::class : Gasto::class;

            if ($origemExternaId !== null
                && $modelClass::doUsuario($usuario->id)->where('origem_externa_id', $origemExternaId)->exists()) {
                $jaExistiam++;

                continue;
            }

            if ($linha['tipo'] === 'renda') {
                $usuario->rendas()->create([
                    'descricao' => $linha['descricao'],
                    'fonte' => $linha['descricao'],
                    'categoria_renda_id' => $linha['categoria_renda_id'] ?? null,
                    'valor' => abs($linha['valor']),
                    'data_recebimento' => $linha['data'],
                    'recorrente' => false,
                    'origem_externa_id' => $origemExternaId,
                ]);
                $rendasCriadas++;
            } else {
                $usuario->gastos()->create([
                    'descricao' => $linha['descricao'],
                    'valor' => abs($linha['valor']),
                    'data' => $linha['data'],
                    'categoria_gasto_id' => $linha['categoria_gasto_id'] ?? null,
                    'origem_externa_id' => $origemExternaId,
                ]);
                $gastosCriados++;
            }
        }

        return response()->json([
            'data' => [
                'rendas_criadas' => $rendasCriadas,
                'gastos_criados' => $gastosCriados,
                'ja_existiam' => $jaExistiam,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapeamentoDaRequisicao(Request $request): ?array
    {
        if (! $request->filled('mapeamento')) {
            return null;
        }

        return [
            'nome' => $request->input('mapeamento.nome'),
            'coluna_data' => $request->input('mapeamento.coluna_data'),
            'coluna_valor' => $request->input('mapeamento.coluna_valor'),
            'coluna_descricao' => $request->input('mapeamento.coluna_descricao'),
            'coluna_identificador' => $request->input('mapeamento.coluna_identificador'),
            'formato_data' => $request->input('mapeamento.formato_data'),
        ];
    }
}
