<?php

namespace App\Models\Task;

use App\Models\Identity\Funcionario;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nome', 'concluida', 'ID_tarefa', 'matricula_colaborador'])]
class Subtarefa extends Model
{
    protected $table = 'subtarefa';

    protected $primaryKey = 'ID_subtarefa';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'concluida' => 'boolean',
        ];
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'ID_tarefa', 'ID_tarefa');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'matricula_colaborador', 'matricula_funcionario');
    }
}
