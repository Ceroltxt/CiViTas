<?php

namespace App\Models\Project;

use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nome',
    'data_inicio',
    'data_previsao_fim',
    'data_conclusao',
    'ativo',
    'ID_matricula_admin',
])]
class Projeto extends Model
{
    protected $table = 'projeto';

    protected $primaryKey = 'ID_projeto';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_inicio' => 'datetime',
            'data_previsao_fim' => 'datetime',
            'data_conclusao' => 'datetime',
            'ativo' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'ID_matricula_admin', 'matricula_funcionario');
    }

    public function equipes(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipe::class,
            'projeto_equipe',
            'ID_projeto',
            'ID_equipe',
        )->withTimestamps();
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class, 'ID_projeto', 'ID_projeto');
    }
}
