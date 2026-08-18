<?php

namespace App\Models\Team;

use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome', 'pontos_totais', 'matricula_gestor'])]
class Equipe extends Model
{
    protected $table = 'equipe';

    protected $primaryKey = 'ID_equipe';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pontos_totais' => 'integer',
        ];
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'matricula_gestor', 'matricula_funcionario');
    }

    public function membros(): BelongsToMany
    {
        return $this->belongsToMany(
            Funcionario::class,
            'equipe_funcionario',
            'ID_equipe',
            'matricula_funcionario',
        )->withTimestamps();
    }

    public function projetos(): BelongsToMany
    {
        return $this->belongsToMany(
            Projeto::class,
            'projeto_equipe',
            'ID_equipe',
            'ID_projeto',
        )->withTimestamps();
    }
}
