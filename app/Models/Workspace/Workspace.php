<?php

namespace App\Models\Workspace;

use App\Models\Identity\Funcionario;
use App\Models\Team\Equipe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = ['nome'];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Funcionario::class,
            'workspace_funcionario',
            'workspace_id',
            'matricula_funcionario'
        )->withPivot('role')->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(WorkspaceInvite::class);
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(Equipe::class, 'workspace_id');
    }
}
