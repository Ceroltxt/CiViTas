<?php

namespace App\Http\Resources\Frontend;

use App\Models\Project\Projeto;
use App\Models\Team\Equipe;
use App\Support\Frontend\ProjectProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTeamResource extends JsonResource
{
    public function __construct(
        Equipe $resource,
        private readonly Projeto $projeto,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Equipe $equipe */
        $equipe = $this->resource;

        return [
            'id' => (string) $equipe->ID_equipe,
            'name' => $equipe->nome,
            'initial' => mb_strtoupper(mb_substr($equipe->nome, 0, 2)),
            'color' => ProjectProgressCalculator::colorForId($equipe->ID_equipe),
            'role' => 'Membro',
            'memberCount' => $equipe->membros()->count(),
            'projectId' => (string) $this->projeto->ID_projeto,
            'description' => "Equipe {$equipe->nome} do projeto {$this->projeto->nome}.",
            'leader' => $equipe->gestor
                ? trim("{$equipe->gestor->nome} {$equipe->gestor->sobrenome}")
                : null,
            'createdAt' => $equipe->created_at?->format('d/m/Y'),
        ];
    }
}
