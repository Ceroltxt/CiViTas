<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome_status'])]
class StatusTarefa extends Model
{
    protected $table = 'status_tarefa';

    protected $primaryKey = 'ID_status_tarefa';

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class, 'ID_status_tarefa', 'ID_status_tarefa');
    }
}
