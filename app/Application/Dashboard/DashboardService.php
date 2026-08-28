<?php

namespace App\Application\Dashboard;

use App\Http\Resources\Frontend\RankingEntryResource;
use App\Http\Resources\Frontend\TaskResource;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use App\Support\Frontend\TaskQueryBuilder;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return list<array{id: string, label: string, value: string}>
     */
    public function metrics(Funcionario $funcionario): array
    {
        $tarefas = TaskQueryBuilder::forFuncionario($funcionario)->get();

        $atribuidas = $tarefas->filter(
            fn (Tarefa $t) => $t->colaboradores->contains('matricula_funcionario', $funcionario->matricula_funcionario),
        )->count();

        $andamento = $tarefas->filter(
            fn (Tarefa $t) => in_array($t->status?->nome_status, ['em-andamento', 'em-revisao', 'validar'], true),
        )->count();
        $atraso = $tarefas->filter(fn (Tarefa $t) => $t->status?->nome_status === 'atrasado')->count();
        $concluidas = $tarefas->filter(fn (Tarefa $t) => $t->status?->nome_status === 'concluido')->count();
        $produtividade = $tarefas->isEmpty()
            ? 0
            : (int) round(($concluidas / $tarefas->count()) * 100);

        return [
            ['id' => 'atribuidas', 'label' => 'Tarefas Atribuídas', 'value' => (string) $atribuidas],
            ['id' => 'andamento', 'label' => 'Em Andamento', 'value' => (string) $andamento],
            ['id' => 'atraso', 'label' => 'Em Atraso', 'value' => (string) $atraso],
            ['id' => 'produtividade', 'label' => 'Produtividade', 'value' => "{$produtividade}%"],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function agenda(Funcionario $funcionario): array
    {
        $today = now()->startOfDay();

        return TaskQueryBuilder::forFuncionario($funcionario)
            ->where(function ($query) use ($today): void {
                $query->whereDate('data_prazo', $today)
                    ->orWhereDate('data_inicio', $today);
            })
            ->with(['projeto', 'equipe'])
            ->orderBy('data_prazo')
            ->limit(10)
            ->get()
            ->map(function (Tarefa $tarefa, int $index): array {
                $tag = match ($tarefa->prioridade) {
                    'critica', 'alta' => ['label' => 'Urgência', 'color' => 'text-rose-500 bg-rose-100'],
                    'media' => ['label' => 'Média', 'color' => 'text-orange-500 bg-orange-100'],
                    default => ['label' => 'Baixa', 'color' => 'text-blue-600 bg-blue-200'],
                };

                return [
                    'id' => 'a'.$tarefa->ID_tarefa,
                    'time' => sprintf('%02d:00', 9 + ($index % 6)),
                    'title' => $tarefa->nome,
                    'description' => $tarefa->projeto?->nome ?? ($tarefa->equipe?->nome ?? 'Tarefa pessoal'),
                    'dotColor' => 'bg-orange-400',
                    'tag' => $tag,
                    'eventId' => 'e-today-'.$tarefa->ID_tarefa,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function ranking(int $limit = 10): array
    {
        return Funcionario::query()
            ->with('cargo')
            ->orderByDesc('pontos_totais')
            ->limit($limit)
            ->get()
            ->map(fn (Funcionario $f, int $i) => (new RankingEntryResource($f, $i + 1))->resolve())
            ->all();
    }

    /**
     * @return Collection<int, Projeto>
     */
    public function projects(Funcionario $funcionario): Collection
    {
        return Projeto::query()
            ->where('ativo', true)
            ->where(function ($query) use ($funcionario): void {
                $query->where('ID_matricula_admin', $funcionario->matricula_funcionario)
                    ->orWhereHas(
                        'equipes.membros',
                        fn ($q) => $q->where('equipe_funcionario.matricula_funcionario', $funcionario->matricula_funcionario),
                    )
                    ->orWhereHas(
                        'tarefas.colaboradores',
                        fn ($q) => $q->where('tarefa_funcionario.matricula_colaborador', $funcionario->matricula_funcionario),
                    );
            })
            ->with(['equipes.gestor', 'equipes.membros', 'tarefas.status'])
            ->get();
    }

    /**
     * @return Collection<int, Equipe>
     */
    public function teams(Funcionario $funcionario): Collection
    {
        return Equipe::query()
            ->whereHas(
                'membros',
                fn ($q) => $q->where('equipe_funcionario.matricula_funcionario', $funcionario->matricula_funcionario),
            )
            ->orWhere('matricula_gestor', $funcionario->matricula_funcionario)
            ->with('membros.cargo')
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function notifications(Funcionario $funcionario, int $limit = 10): array
    {
        $tarefaIds = TaskQueryBuilder::forFuncionario($funcionario)->pluck('ID_tarefa');

        return HistoricoTarefa::query()
            ->with(['tarefa', 'funcionario'])
            ->whereIn('ID_tarefa', $tarefaIds)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (HistoricoTarefa $historico): array {
                return [
                    'id' => 'n'.$historico->ID_historico,
                    'icon' => 'i-heroicons-clipboard-document',
                    'iconColor' => 'text-pink-600 bg-pink-100',
                    'title' => $historico->acao,
                    'description' => $historico->detalhes ?? ($historico->tarefa?->nome ?? ''),
                    'time' => $historico->created_at?->locale('pt_BR')->diffForHumans() ?? '',
                    'unread' => $historico->created_at?->isToday() ?? false,
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function calendarEvents(Funcionario $funcionario): array
    {
        $colors = ['pink', 'blue', 'amber', 'violet', 'green'];

        return TaskQueryBuilder::forFuncionario($funcionario)
            ->whereNotNull('data_prazo')
            ->whereMonth('data_prazo', now()->month)
            ->whereYear('data_prazo', now()->year)
            ->orderBy('data_prazo')
            ->get()
            ->map(function (Tarefa $tarefa, int $index) use ($colors): array {
                return [
                    'id' => 'e'.$tarefa->ID_tarefa,
                    'title' => $tarefa->nome,
                    'startDay' => (int) $tarefa->data_prazo?->day,
                    'length' => 1,
                    'color' => $colors[$index % count($colors)],
                    'editable' => true,
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(Funcionario $funcionario): array
    {
        $tasks = TaskQueryBuilder::forFuncionario($funcionario)
            ->whereNotNull('data_inicio')
            ->whereNotNull('data_prazo')
            ->with(['colaboradores', 'projeto'])
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        $origin = $tasks->min(fn (Tarefa $t) => $t->data_inicio)->startOfDay();
        $colors = ['neutral', 'amber', 'blue', 'violet', 'pink'];

        $mapped = $tasks->map(function (Tarefa $tarefa, int $index) use ($origin, $colors): array {
            $assignee = $tarefa->colaboradores->first();

            return [
                'id' => (string) $tarefa->ID_tarefa,
                'title' => $tarefa->nome,
                'assignee' => $assignee
                    ? (new \App\Http\Resources\Frontend\UserSummaryResource($assignee))->resolve()
                    : ['id' => '0', 'name' => 'Sem responsável'],
                'group' => $tarefa->projeto?->nome,
                'startIndex' => max(0, (int) $origin->diffInDays($tarefa->data_inicio)),
                'span' => max(1, (int) $tarefa->data_inicio->diffInDays($tarefa->data_prazo) + 1),
                'color' => $colors[$index % count($colors)],
            ];
        });

        $groups = $mapped->groupBy('group');

        return $groups->map(function (Collection $groupTasks, ?string $title): array {
            return [
                'title' => $title ?: null,
                'tasks' => $groupTasks->map(fn (array $task) => collect($task)->except('group')->all())->values()->all(),
            ];
        })->values()->all();
    }

    public function boardTasks(Funcionario $funcionario): Collection
    {
        return TaskQueryBuilder::forFuncionario($funcionario)
            ->where('pessoal', false)
            ->get();
    }

    public function tasks(Funcionario $funcionario): Collection
    {
        return TaskQueryBuilder::forFuncionario($funcionario)->get();
    }

    public function auditLogs(Funcionario $funcionario, int $limit = 10): Collection
    {
        $tarefaIds = TaskQueryBuilder::forFuncionario($funcionario)->pluck('ID_tarefa');

        return HistoricoTarefa::query()
            ->with('funcionario')
            ->whereIn('ID_tarefa', $tarefaIds)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function currentProject(Funcionario $funcionario): ?Projeto
    {
        return $this->projects($funcionario)->first();
    }
}
