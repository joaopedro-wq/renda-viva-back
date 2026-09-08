<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmarImportacaoRequest extends FormRequest
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
            'linhas' => ['required', 'array', 'min:1'],
            'linhas.*.data' => ['required', 'date'],
            'linhas.*.valor' => ['required', 'numeric'],
            'linhas.*.descricao' => ['required', 'string', 'max:255'],
            'linhas.*.identificador_externo' => ['nullable', 'string', 'max:255'],
            'linhas.*.tipo' => ['required', Rule::in(['renda', 'gasto'])],
            'linhas.*.categoria_gasto_id' => ['nullable', 'exists:categorias_gasto,id'],
            'linhas.*.categoria_renda_id' => ['nullable', 'exists:categorias_renda,id'],
        ];
    }
}
