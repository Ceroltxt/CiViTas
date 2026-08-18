<?php

namespace App\Models\Task;

use App\Models\Identity\Funcionario;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acao', 'detalhes', 'ID_tarefa', 'matricula_funcionario'])]
class HistoricoTarefa extends Model
{
    protected $table = 'historico_tarefa';

    protected $primaryKey = 'ID_historico';

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'ID_tarefa', 'ID_tarefa');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'matricula_funcionario', 'matricula_funcionario');
    }
}
