<?php

namespace App\Http\Resources\Frontend;

use App\Models\Project\Projeto;
use App\Models\Team\Equipe;
use App\Support\Frontend\ProjectProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Projeto */
class ProjectProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->ID_projeto,
            'name' => $this->nome,
            'description' => $this->descricao,
            'prioridade' => $this->prioridade ?? 'media',
            'ativo' => (bool) $this->ativo,
            'status' => $this->ativo ? 'ativo' : 'concluido',
            'data_inicio' => $this->data_inicio,
            'data_previsao_fim' => $this->data_previsao_fim,
            'progress' => ProjectProgressCalculator::forProject($this->resource),
            'color' => ProjectProgressCalculator::colorForId($this->ID_projeto),
            'teams' => $this->whenLoaded(
                'equipes',
                fn () => $this->equipes->map(fn (Equipe $equipe) => new ProjectTeamResource($equipe, $this->resource)),
            ),
        ];
    }
}
