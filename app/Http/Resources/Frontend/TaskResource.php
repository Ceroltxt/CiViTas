<?php

namespace App\Http\Resources\Frontend;

use App\Models\Task\Tarefa;
use App\Support\Frontend\ProjectProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tarefa */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status?->nome_status ?? 'a-fazer';
        $projectProgress = ProjectProgressCalculator::forTask($this->resource);
        $subtaskProgress = $this->subtaskProgress();

        return [
            'id' => (string) $this->ID_tarefa,
            'title' => $this->nome,
            'priority' => $this->prioridade ?? 'media',
            'status' => $status,
            'project' => $this->when(! $this->pessoal, $this->projeto?->nome),
            'team' => $this->when(! $this->pessoal, $this->equipe?->nome),
            'dueDate' => $this->data_prazo?->locale('pt_BR')->translatedFormat('d M'),
            'projectProgress' => $this->when(! $this->pessoal, $projectProgress),
            'progress' => $subtaskProgress,
            'assignees' => UserSummaryResource::collection($this->whenLoaded('colaboradores')),
            'note' => $this->overdueNote(),
            'personal' => $this->pessoal ? true : null,
            'notStarted' => $subtaskProgress === 0 && $status === 'a-fazer' ? true : null,
            'subtasks' => SubtaskResource::collection($this->whenLoaded('subtarefas')),
            'auditLog' => TaskAuditEntryResource::collection($this->whenLoaded('historico')),
            'stars' => min(3, (int) floor(($this->pontos_base ?? 0) / 100)),
            'description' => $this->descricao,
            'startDate' => $this->data_inicio?->format('d/m/Y'),
            'type' => $this->tipo,
            'complexity' => $this->complexidade,
            'category' => $this->categoria,
            'program' => $this->programa,
            'completedDate' => $this->data_conclusao?->format('d/m/Y'),
        ];
    }

    private function subtaskProgress(): int
    {
        $subtarefas = $this->subtarefas;

        if ($subtarefas->isEmpty()) {
            return match ($this->status?->nome_status) {
                'concluido' => 100,
                'em-andamento', 'em-revisao', 'validar' => 50,
                default => 0,
            };
        }

        $completed = $subtarefas->where('concluida', true)->count();

        return (int) round(($completed / $subtarefas->count()) * 100);
    }

    private function overdueNote(): ?string
    {
        if ($this->status?->nome_status !== 'atrasado' || ! $this->data_prazo) {
            return null;
        }

        $days = now()->startOfDay()->diffInDays($this->data_prazo, false);

        if ($days >= 0) {
            return null;
        }

        $weeks = (int) ceil(abs($days) / 7);

        return "Atrasada há {$weeks} ".($weeks === 1 ? 'semana' : 'semanas');
    }
}
