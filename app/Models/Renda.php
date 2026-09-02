<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renda extends Model
{
    use HasFactory;

    protected $table = 'rendas';

    protected $fillable = [
        'usuario_id',
        'descricao',
        'fonte',
        'valor',
        'data_recebimento',
        'recorrente',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_recebimento' => 'date',
            'recorrente' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Nunca listar renda de outro usuário — todo endpoint passa por aqui. */
    public function scopeDoUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
