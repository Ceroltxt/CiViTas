<?php

namespace App\Http\Resources\Frontend;

use App\Models\Team\Equipe;
use App\Support\Frontend\ProjectProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Equipe */
class TeamSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $taskCount = $this->projetos()
            ->withCount('tarefas')
            ->get()
            ->sum('tarefas_count');

        return [
            'id' => (string) $this->ID_equipe,
            'name' => $this->nome,
            'initial' => mb_strtoupper(mb_substr($this->nome, 0, 1)),
            'color' => ProjectProgressCalculator::colorForId($this->ID_equipe),
            'description' => $this->membros->count().' Colaborador(es) · '.$taskCount.' Tarefa(s)',
            'members' => UserSummaryResource::collection($this->whenLoaded('membros')),
            'leader' => $this->whenLoaded('gestor', fn () => trim("{$this->gestor->nome} {$this->gestor->sobrenome}")),
            'gestorId' => (string) $this->matricula_gestor,
            'memberCount' => $this->whenLoaded('membros', fn () => $this->membros->count()),
            'taskCount' => $taskCount,
        ];
    }
}
