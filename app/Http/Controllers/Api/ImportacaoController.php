<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmarImportacaoRequest;
use App\Http\Requests\PreVisualizarImportacaoRequest;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\ModeloImportacao;
use App\Models\Renda;
use App\Services\ClassificacaoImportService;
use App\Services\ExtratoParserService;
use App\Services\LinhaClassificada;

/**
 * Import nunca grava direto — só sugere (ver ClassificacaoImportService).
 * `preVisualizar` não persiste lançamento nenhum, só (no máximo) o
 * `ModeloImportacao` quando o usuário acabou de mapear um formato novo.
 * `confirmar` é o único ponto que grava `rendas`/`gastos`.
 */
class ImportacaoController extends Controller
{
    public function preVisualizar(
        PreVisualizarImportacaoRequest $request,
        ExtratoParserService $parser,
        ClassificacaoImportService $classificador
    ) {
        $conteudo = $request->file('arquivo')->get();
        $usuario = $request->user();

        $modelo = $this->resolverModelo($request, $conteudo, $parser);

        if ($modelo === null) {
            return response()->json([
                'precisa_mapear' => true,
                'cabecalho' => $parser->cabecalho($conteudo),
            ]);
        }

        $linhas = $parser->parsear($conteudo, $modelo);

        $origensJaImportadas = [
            ...Renda::doUsuario($usuario->id)->whereNotNull('origem_externa_id')->pluck('origem_externa_id'),
            ...Gasto::doUsuario($usuario->id)->whereNotNull('origem_externa_id')->pluck('origem_externa_id'),
        ];

        $classificadas = $classificador->classificar(
            $linhas,
            $origensJaImportadas,
            CategoriaGasto::all(),
            $this->categoriaRendaPorDescricao($usuario->id)
        );

        return response()->json([
            'precisa_mapear' => false,
            'modelo_importacao_id' => $modelo->id,
            'data' => array_map($this->linhaClassificadaParaArray(...), $classificadas),
        ]);
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
     * Cabeçalho exato do extrato de conta do Nubank — validado nesta sessão
     * contra um arquivo real. Reconhecido automaticamente, sem passar pela
     * tela de mapeamento manual: é o formato de referência da v1 (ver
     * PROXIMAS-FEATURES.md, Fase 8). Outros formatos ainda caem no
     * mapeamento manual — esse fallback não substitui o mecanismo genérico,
     * só evita fricção no caso mais comum enquanto ele é o único validado.
     */
    private const CABECALHO_NUBANK_CONTA = ['Data', 'Valor', 'Identificador', 'Descrição'];

    /**
     * Resolve, nessa ordem: (1) modelo informado explicitamente; (2) modelo
     * salvo cujo cabeçalho bate com o do arquivo; (3) mapeamento manual
     * enviado agora; (4) formato Nubank Conta reconhecido automaticamente.
     * Sem nenhum dos quatro, devolve null (front mostra a tela de
     * mapeamento). Em (3) e (4), o resultado vira um `ModeloImportacao`
     * novo — a próxima importação desse usuário cai direto em (2).
     */
    private function resolverModelo(
        PreVisualizarImportacaoRequest $request,
        string $conteudo,
        ExtratoParserService $parser
    ): ?ModeloImportacao {
        $usuario = $request->user();

        if ($request->filled('modelo_importacao_id')) {
            return ModeloImportacao::doUsuario($usuario->id)->findOrFail($request->integer('modelo_importacao_id'));
        }

        $assinatura = $parser->assinaturaColunas($conteudo);

        $modeloExistente = ModeloImportacao::doUsuario($usuario->id)
            ->where('assinatura_colunas', $assinatura)
            ->first();

        if ($modeloExistente) {
            return $modeloExistente;
        }

        $mapeamento = $this->mapeamentoDaRequisicao($request) ?? $this->mapeamentoNubankSeReconhecido($parser->cabecalho($conteudo));

        if ($mapeamento === null) {
            return null;
        }

        return ModeloImportacao::create([
            'usuario_id' => $usuario->id,
            'assinatura_colunas' => $assinatura,
            ...$mapeamento,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapeamentoDaRequisicao(PreVisualizarImportacaoRequest $request): ?array
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

    /**
     * @param  array<int, string>  $cabecalho
     * @return array<string, mixed>|null
     */
    private function mapeamentoNubankSeReconhecido(array $cabecalho): ?array
    {
        if (array_diff(self::CABECALHO_NUBANK_CONTA, $cabecalho) !== []) {
            return null;
        }

        return [
            'nome' => 'Nubank Conta',
            'coluna_data' => 'Data',
            'coluna_valor' => 'Valor',
            'coluna_descricao' => 'Descrição',
            'coluna_identificador' => 'Identificador',
            'formato_data' => 'd/m/Y',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaClassificadaParaArray(LinhaClassificada $classificada): array
    {
        return [
            'data' => $classificada->linha->data,
            'valor' => $classificada->linha->valor,
            'descricao' => $classificada->linha->descricao,
            'identificador_externo' => $classificada->linha->identificadorExterno,
            'tipo_sugerido' => $classificada->tipoSugerido,
            // categoria_gasto_id quando tipo_sugerido é 'gasto', categoria_renda_id
            // quando é 'renda' — nunca os dois, o front já sabe distinguir pelo tipo.
            'categoria_sugerida_id' => $classificada->categoriaSugeridaId,
            'ja_importado' => $classificada->jaImportado,
            'motivo' => $classificada->motivo,
        ];
    }

    /**
     * O "aprendizado" do import: pra cada descrição de extrato que esse
     * usuário já categorizou como renda antes, guarda a categoria escolhida —
     * o mesmo pagador tende a repetir a mesma descrição todo mês (salário,
     * cliente fixo de freela etc.), então a sugestão do próximo mês já vem
     * pronta. Mantém a ocorrência mais recente quando a mesma descrição foi
     * categorizada de formas diferentes ao longo do tempo.
     *
     * @return array<string, int>
     */
    private function categoriaRendaPorDescricao(int $usuarioId): array
    {
        return Renda::doUsuario($usuarioId)
            ->whereNotNull('categoria_renda_id')
            ->orderByDesc('data_recebimento')
            ->get(['descricao', 'categoria_renda_id'])
            ->reduce(function (array $mapa, Renda $renda) {
                $mapa[$renda->descricao] ??= $renda->categoria_renda_id;

                return $mapa;
            }, []);
    }
}
