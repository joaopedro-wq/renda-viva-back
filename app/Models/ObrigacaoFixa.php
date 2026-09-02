<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObrigacaoFixa extends Model
{
    use HasFactory;

    protected $table = 'obrigacoes_fixas';

    protected $fillable = [
        'usuario_id',
        'descricao',
        'valor',
        'dia_vencimento',
        'ativa',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'dia_vencimento' => 'integer',
            'ativa' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function scopeDoUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }
}
