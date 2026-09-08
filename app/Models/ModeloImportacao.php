<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeloImportacao extends Model
{
    use HasFactory;

    protected $table = 'modelos_importacao';

    protected $fillable = [
        'usuario_id',
        'nome',
        'assinatura_colunas',
        'coluna_data',
        'coluna_valor',
        'coluna_descricao',
        'coluna_identificador',
        'formato_data',
        'convencao_sinal',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeDoUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }
}
