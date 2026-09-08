<?php

namespace App\Services;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\ModeloImportacao;
use App\Models\Renda;
use App\Models\User;

/**
 * Orquestra a pré-visualização de um extrato já disponível como string (seja de um upload
 * único ou de um arquivo reagrupado a partir de chunks): resolve/detecta o `ModeloImportacao`,
 * parseia e classifica. Nunca grava lançamento — só (no máximo) cria o `ModeloImportacao`
 * quando o usuário acabou de mapear um formato novo. Extraído de
 * `ImportacaoController::preVisualizar()` pra ser reaproveitado pelo endpoint de upload em
 * chunks sem duplicar a lógica.
 */
class ImportacaoPreVisualizacaoService
{
    public function __construct(
        private readonly ExtratoParserService $parser,
        private readonly ClassificacaoImportService $classificador,
    ) {}

    /**
     * @return array<string, mixed> `{precisa_mapear: true, cabecalho}` ou
     *                               `{precisa_mapear: false, modelo_importacao_id, data}`
     */
    public function processar(
        User $usuario,
        string $conteudo,
        ?int $modeloImportacaoId,
        ?array $mapeamento
    ): array {
        $modelo = $this->resolverModelo($usuario, $conteudo, $modeloImportacaoId, $mapeamento);

        if ($modelo === null) {
            return [
                'precisa_mapear' => true,
                'cabecalho' => $this->parser->cabecalho($conteudo),
            ];
        }

        $linhas = $this->parser->parsear($conteudo, $modelo);

        $origensJaImportadas = [
            ...Renda::doUsuario($usuario->id)->whereNotNull('origem_externa_id')->pluck('origem_externa_id'),
            ...Gasto::doUsuario($usuario->id)->whereNotNull('origem_externa_id')->pluck('origem_externa_id'),
        ];

        $classificadas = $this->classificador->classificar(
            $linhas,
            $origensJaImportadas,
            CategoriaGasto::all(),
            $this->categoriaRendaPorDescricao($usuario->id)
        );

        return [
            'precisa_mapear' => false,
            'modelo_importacao_id' => $modelo->id,
            'data' => array_map($this->linhaClassificadaParaArray(...), $classificadas),
        ];
    }

   
    private const CABECALHO_NUBANK_CONTA = ['Data', 'Valor', 'Identificador', 'Descrição'];

   
    private function resolverModelo(
        User $usuario,
        string $conteudo,
        ?int $modeloImportacaoId,
        ?array $mapeamento
    ): ?ModeloImportacao {
        if ($modeloImportacaoId !== null) {
            return ModeloImportacao::doUsuario($usuario->id)->findOrFail($modeloImportacaoId);
        }

        $assinatura = $this->parser->assinaturaColunas($conteudo);

        $modeloExistente = ModeloImportacao::doUsuario($usuario->id)
            ->where('assinatura_colunas', $assinatura)
            ->first();

        if ($modeloExistente) {
            return $modeloExistente;
        }

        $mapeamentoResolvido = $mapeamento ?? $this->mapeamentoNubankSeReconhecido($this->parser->cabecalho($conteudo));

        if ($mapeamentoResolvido === null) {
            return null;
        }

        return ModeloImportacao::create([
            'usuario_id' => $usuario->id,
            'assinatura_colunas' => $assinatura,
            ...$mapeamentoResolvido,
        ]);
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
     * O "aprendizado" do import: pra cada descrição de extrato que esse usuário já
     * categorizou como renda antes, guarda a categoria escolhida — o mesmo pagador tende a
     * repetir a mesma descrição todo mês (salário, cliente fixo de freela etc.), então a
     * sugestão do próximo mês já vem pronta. Mantém a ocorrência mais recente quando a mesma
     * descrição foi categorizada de formas diferentes ao longo do tempo.
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
