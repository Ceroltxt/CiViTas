<?php

namespace App\Support\Frontend;

use App\Models\Project\Projeto;
use App\Models\Task\Tarefa;
use Illuminate\Support\Collection;

class ProjectProgressCalculator
{
    public static function forProject(Projeto $projeto): int
    {
        /** @var Collection<int, Tarefa> $tarefas */
        $tarefas = $projeto->tarefas;

        if ($tarefas->isEmpty()) {
            return 0;
        }

        $completed = $tarefas->filter(
            fn (Tarefa $tarefa) => $tarefa->status?->nome_status === 'concluido',
        )->count();

        return (int) round(($completed / $tarefas->count()) * 100);
    }

    public static function forTask(Tarefa $tarefa): int
    {
        if ($tarefa->pessoal || ! $tarefa->ID_projeto) {
            return 0;
        }

        $tarefa->loadMissing('projeto.tarefas.status');

        return $tarefa->projeto
            ? self::forProject($tarefa->projeto)
            : 0;
    }

    /**
     * @return list<string>
     */
    public static function colors(): array
    {
        return [
            'bg-blue-500',
            'bg-violet-500',
            'bg-amber-400',
            'bg-pink-500',
            'bg-cyan-500',
            'bg-indigo-500',
            'bg-emerald-500',
            'bg-orange-500',
        ];
    }

    public static function colorForId(int|string $id): string
    {
        $colors = self::colors();
        $index = abs(crc32((string) $id)) % count($colors);

        return $colors[$index];
    }
}
