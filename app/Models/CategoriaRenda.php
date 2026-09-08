<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catálogo global — mesmo padrão de CategoriaGasto, sem scopeDoUsuario. */
class CategoriaRenda extends Model
{
    protected $table = 'categorias_renda';

    protected $fillable = [
        'nome',
        'icone',
        'cor',
    ];

    public function rendas(): HasMany
    {
        return $this->hasMany(Renda::class);
    }
}
