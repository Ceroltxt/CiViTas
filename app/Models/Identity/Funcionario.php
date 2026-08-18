<?php

namespace App\Models\Identity;

use App\Models\Authorization\PermissaoCargo;
use App\Models\Gamification\PontosUsuario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use App\Models\Project\Projeto;
use App\Models\Task\Subtarefa;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'nome',
    'sobrenome',
    'data_nascimento',
    'email',
    'CPF',
    'pontos_totais',
    'senha',
    'ID_departamento',
    'ID_cargo',
])]
#[Hidden(['senha'])]
class Funcionario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'funcionario';

    protected $primaryKey = 'matricula_funcionario';

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'pontos_totais' => 'integer',
            'senha' => 'hashed',
        ];
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'ID_departamento', 'ID_departamento');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'ID_cargo', 'ID_cargo');
    }

    public function permissoesCargo(): BelongsToMany
    {
        return $this->belongsToMany(
            PermissaoCargo::class,
            'permissao_cargo_funcionario',
            'matricula_funcionario',
            'ID_CP',
        )->withTimestamps();
    }

    public function equipes(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipe::class,
            'equipe_funcionario',
            'matricula_funcionario',
            'ID_equipe',
        )->withTimestamps();
    }

    public function projetosAdministrados(): HasMany
    {
        return $this->hasMany(Projeto::class, 'ID_matricula_admin', 'matricula_funcionario');
    }

    public function tarefasGerenciadas(): HasMany
    {
        return $this->hasMany(Tarefa::class, 'matricula_gestor', 'matricula_funcionario');
    }

    public function tarefasAtribuidas(): BelongsToMany
    {
        return $this->belongsToMany(
            Tarefa::class,
            'tarefa_funcionario',
            'matricula_colaborador',
            'ID_tarefa',
        )->withTimestamps();
    }

    public function subtarefas(): HasMany
    {
        return $this->hasMany(Subtarefa::class, 'matricula_colaborador', 'matricula_funcionario');
    }

    public function pontos(): HasMany
    {
        return $this->hasMany(PontosUsuario::class, 'matricula_funcionario', 'matricula_funcionario');
    }
}
