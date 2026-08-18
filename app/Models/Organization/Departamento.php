<?php

namespace App\Models\Organization;

use App\Models\Identity\Funcionario;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome_departamento'])]
class Departamento extends Model
{
    protected $table = 'departamento';

    protected $primaryKey = 'ID_departamento';

    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class, 'ID_departamento', 'ID_departamento');
    }
}
