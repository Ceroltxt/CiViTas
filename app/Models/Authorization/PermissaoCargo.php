<?php

namespace App\Models\Authorization;

use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['ativo', 'data_atribuicao', 'data_expiracao', 'ID_cargo', 'ID_permissao'])]
class PermissaoCargo extends Model
{
    protected $table = 'permissao_cargo';

    protected $primaryKey = 'ID_CP';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'data_atribuicao' => 'datetime',
            'data_expiracao' => 'datetime',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'ID_cargo', 'ID_cargo');
    }

    public function permissao(): BelongsTo
    {
        return $this->belongsTo(Permissao::class, 'ID_permissao', 'ID_permissao');
    }

    public function funcionarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Funcionario::class,
            'permissao_cargo_funcionario',
            'ID_CP',
            'matricula_funcionario',
        )->withTimestamps();
    }
}
