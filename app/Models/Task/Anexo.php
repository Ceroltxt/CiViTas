<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nome', 'url', 'tipo', 'ID_tarefa'])]
class Anexo extends Model
{
    protected $table = 'anexo';

    protected $primaryKey = 'ID_anexo';

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'ID_tarefa', 'ID_tarefa');
    }
}
