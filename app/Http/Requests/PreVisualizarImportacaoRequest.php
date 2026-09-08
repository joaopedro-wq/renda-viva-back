<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreVisualizarImportacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],

            // Usa um modelo já salvo diretamente — precisa ser do próprio usuário.
            'modelo_importacao_id' => [
                'nullable',
                Rule::exists('modelos_importacao', 'id')->where(
                    fn ($query) => $query->where('usuario_id', $this->user()->id)
                ),
            ],

            // Mapeamento manual — opcional. Sem ele e sem um modelo salvo que
            // reconheça o cabeçalho, o endpoint devolve `precisa_mapear: true`
            // em vez de tentar adivinhar.
            'mapeamento' => ['nullable', 'array'],
            'mapeamento.nome' => ['required_with:mapeamento', 'string', 'max:255'],
            'mapeamento.coluna_data' => ['required_with:mapeamento', 'string'],
            'mapeamento.coluna_valor' => ['required_with:mapeamento', 'string'],
            'mapeamento.coluna_descricao' => ['required_with:mapeamento', 'string'],
            'mapeamento.coluna_identificador' => ['nullable', 'string'],
            'mapeamento.formato_data' => ['required_with:mapeamento', 'string'],
        ];
    }
}
