<?php

namespace App\Models\Organization;

use App\Models\Authorization\Permissao;
use App\Models\Identity\Funcionario;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome_cargo'])]
class Cargo extends Model
{
    protected $table = 'cargo';

    protected $primaryKey = 'ID_cargo';

    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class, 'ID_cargo', 'ID_cargo');
    }

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(
            Permissao::class,
            'permissao_cargo',
            'ID_cargo',
            'ID_permissao',
        )->withPivot('ID_CP', 'ativo', 'data_atribuicao', 'data_expiracao')
            ->withTimestamps();
    }
}
