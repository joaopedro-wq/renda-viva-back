<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGastoRequest extends FormRequest
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
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
            // Categoria é catálogo global — só precisa existir, sem checar dono.
            'categoria_gasto_id' => ['nullable', 'exists:categorias_gasto,id'],
            // Obrigação fixa é do usuário — nunca aceitar id de outro usuário aqui.
            'obrigacao_fixa_id' => [
                'nullable',
                Rule::exists('obrigacoes_fixas', 'id')->where(
                    fn ($query) => $query->where('usuario_id', $this->user()->id)
                ),
            ],
        ];
    }
}
