<?php

namespace App\Models\Authorization;

use App\Models\Organization\Cargo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome_permissao'])]
class Permissao extends Model
{
    protected $table = 'permissoes';

    protected $primaryKey = 'ID_permissao';

    public function cargos(): BelongsToMany
    {
        return $this->belongsToMany(
            Cargo::class,
            'permissao_cargo',
            'ID_permissao',
            'ID_cargo',
        )->withPivot('ID_CP', 'ativo', 'data_atribuicao', 'data_expiracao')
            ->withTimestamps();
    }
}
