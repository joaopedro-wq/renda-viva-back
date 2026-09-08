<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UploadChunkImportacaoRequest extends FormRequest
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
            // ~1MB de folga sobre os ~512KB reais de cada chunk (overhead do multipart).
            'arquivo' => ['required', 'file', 'max:1024'],

            'modelo_importacao_id' => [
                'nullable',
                Rule::exists('modelos_importacao', 'id')->where(
                    fn ($query) => $query->where('usuario_id', $this->user()->id)
                ),
            ],

            // Mapeamento manual — só faz sentido junto do chunk final, mas a validação
            // não sabe (nem precisa saber) qual chunk é o último.
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
