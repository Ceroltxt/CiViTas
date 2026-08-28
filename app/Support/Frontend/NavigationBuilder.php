<?php

namespace App\Support\Frontend;

class NavigationBuilder
{
    /**
     * @return list<array{label: string, icon: string, to: string}>
     */
    public static function forRole(string $appRole): array
    {
        return match ($appRole) {
            'admin' => [
                ['label' => 'Início', 'icon' => 'i-heroicons-home', 'to' => '/admin'],
                ['label' => 'Quadros', 'icon' => 'i-heroicons-squares-2x2', 'to' => '/admin/quadros'],
                ['label' => 'Projetos', 'icon' => 'i-heroicons-folder', 'to' => '/admin/projetos'],
                ['label' => 'Acessos', 'icon' => 'i-heroicons-key', 'to' => '/admin/acessos'],
                ['label' => 'Relatorios', 'icon' => 'i-heroicons-shield-check', 'to' => '/admin/relatorios'],
                ['label' => 'Configurações', 'icon' => 'i-heroicons-cog-6-tooth', 'to' => '/admin/configuracoes'],
            ],
            'gestor' => [
                ['label' => 'Dashboard', 'icon' => 'i-heroicons-home', 'to' => '/gestor'],
                ['label' => 'Equipes', 'icon' => 'i-heroicons-user-group', 'to' => '/gestor/equipes'],
                ['label' => 'Projetos', 'icon' => 'i-heroicons-folder', 'to' => '/gestor/projetos'],
                ['label' => 'Relatórios', 'icon' => 'i-heroicons-chart-bar', 'to' => '/gestor/relatorios'],
                ['label' => 'Configurações', 'icon' => 'i-heroicons-cog-6-tooth', 'to' => '/gestor/configuracoes'],
            ],
            default => [
                ['label' => 'Início', 'icon' => 'i-heroicons-home', 'to' => '/colaborador'],
                ['label' => 'Minhas Tarefas', 'icon' => 'i-heroicons-clipboard-document-list', 'to' => '/colaborador/minhas-tarefas'],
                ['label' => 'Projetos', 'icon' => 'i-heroicons-folder', 'to' => '/colaborador/projetos'],
                ['label' => 'Ranking', 'icon' => 'i-heroicons-trophy', 'to' => '/colaborador/ranking'],
                ['label' => 'Relatórios', 'icon' => 'i-heroicons-document-chart-bar', 'to' => '/colaborador/relatorios'],
                ['label' => 'Configurações', 'icon' => 'i-heroicons-cog-6-tooth', 'to' => '/colaborador/configuracoes'],
            ],
        };
    }
}
