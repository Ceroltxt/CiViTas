<?php

namespace App\Models\Gamification;

use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pontos', 'acao', 'data', 'matricula_funcionario', 'ID_tarefa'])]
class PontosUsuario extends Model
{
    protected $table = 'pontos_usuario';

    protected $primaryKey = 'ID_pontos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pontos' => 'integer',
            'data' => 'datetime',
        ];
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'matricula_funcionario', 'matricula_funcionario');
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'ID_tarefa', 'ID_tarefa');
    }
}
