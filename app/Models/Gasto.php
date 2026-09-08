<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';

    protected $fillable = [
        'usuario_id',
        'categoria_gasto_id',
        'obrigacao_fixa_id',
        'descricao',
        'valor',
        'data',
        'origem_externa_id',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class);
    }

    public function obrigacaoFixa(): BelongsTo
    {
        return $this->belongsTo(ObrigacaoFixa::class);
    }

    public function scopeDoUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
