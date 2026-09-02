<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGastoRequest extends FormRequest
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
            'descricao' => ['sometimes', 'required', 'string', 'max:255'],
            'valor' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'data' => ['sometimes', 'required', 'date'],
            'categoria_gasto_id' => ['nullable', 'exists:categorias_gasto,id'],
            'obrigacao_fixa_id' => [
                'nullable',
                Rule::exists('obrigacoes_fixas', 'id')->where(
                    fn ($query) => $query->where('usuario_id', $this->user()->id)
                ),
            ],
        ];
    }
}
