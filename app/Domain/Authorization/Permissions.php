<?php

namespace App\Domain\Authorization;

/**
 * Slugs de permissão persistidos em `permissoes.nome_permissao`.
 *
 * Cargos recebem subsets via `permissao_cargo`. Policies consultam
 * `Funcionario::hasPermission()`; o escopo (qual tarefa/projeto) fica na Policy.
 */
final class Permissions
{
    // ── Tarefas ──────────────────────────────────────────────────────────
    public const TASKS_VIEW = 'tasks.view';

    public const TASKS_CREATE = 'tasks.create';

    public const TASKS_CREATE_PERSONAL = 'tasks.create-personal';

    public const TASKS_UPDATE = 'tasks.update';

    public const TASKS_DELETE = 'tasks.delete';

    public const TASKS_ASSIGN = 'tasks.assign';

    public const TASKS_ATTACH = 'tasks.attach';

    // ── Projetos ─────────────────────────────────────────────────────────
    public const PROJECTS_VIEW = 'projects.view';

    public const PROJECTS_MANAGE = 'projects.manage';

    // ── Equipes ──────────────────────────────────────────────────────────
    public const TEAMS_VIEW = 'teams.view';

    public const TEAMS_MANAGE = 'teams.manage';

    // ── Administração ────────────────────────────────────────────────────
    public const ACCESS_VIEW = 'admin.access.view';

    public const ACCESS_MANAGE = 'admin.access.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TASKS_VIEW,
            self::TASKS_CREATE,
            self::TASKS_CREATE_PERSONAL,
            self::TASKS_UPDATE,
            self::TASKS_DELETE,
            self::TASKS_ASSIGN,
            self::TASKS_ATTACH,
            self::PROJECTS_VIEW,
            self::PROJECTS_MANAGE,
            self::TEAMS_VIEW,
            self::TEAMS_MANAGE,
            self::ACCESS_VIEW,
            self::ACCESS_MANAGE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function forColaborador(): array
    {
        return [
            self::TASKS_VIEW,
            self::TASKS_CREATE,
            self::TASKS_CREATE_PERSONAL,
            self::TASKS_UPDATE,
            self::TASKS_DELETE,
            self::TASKS_ATTACH,
            self::PROJECTS_VIEW,
            self::TEAMS_VIEW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function forGestor(): array
    {
        return [
            ...self::forColaborador(),
            self::TASKS_ASSIGN,
            self::PROJECTS_MANAGE,
            self::TEAMS_MANAGE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function forAdmin(): array
    {
        return self::all();
    }
}
