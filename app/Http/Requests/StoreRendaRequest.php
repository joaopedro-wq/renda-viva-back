<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRendaRequest extends FormRequest
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
            'fonte' => ['required', 'string', 'max:255'],
            'categoria_renda_id' => ['nullable', 'exists:categorias_renda,id'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data_recebimento' => ['required', 'date'],
            'recorrente' => ['sometimes', 'boolean'],
        ];
    }
}
