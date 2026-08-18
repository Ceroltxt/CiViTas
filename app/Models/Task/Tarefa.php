<?php

namespace App\Models\Task;

use App\Models\Gamification\PontosUsuario;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nome',
    'descricao',
    'pontos_base',
    'matricula_gestor',
    'ID_projeto',
    'ID_status_tarefa',
])]
class Tarefa extends Model
{
    protected $table = 'tarefa';

    protected $primaryKey = 'ID_tarefa';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pontos_base' => 'integer',
        ];
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'matricula_gestor', 'matricula_funcionario');
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class, 'ID_projeto', 'ID_projeto');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusTarefa::class, 'ID_status_tarefa', 'ID_status_tarefa');
    }

    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(
            Funcionario::class,
            'tarefa_funcionario',
            'ID_tarefa',
            'matricula_colaborador',
        )->withTimestamps();
    }

    public function subtarefas(): HasMany
    {
        return $this->hasMany(Subtarefa::class, 'ID_tarefa', 'ID_tarefa');
    }

    public function historico(): HasMany
    {
        return $this->hasMany(HistoricoTarefa::class, 'ID_tarefa', 'ID_tarefa');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class, 'ID_tarefa', 'ID_tarefa');
    }

    public function pontos(): HasMany
    {
        return $this->hasMany(PontosUsuario::class, 'ID_tarefa', 'ID_tarefa');
    }
}
