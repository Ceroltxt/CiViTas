<?php

namespace App\Domain\Authorization;

/**
 * Perfil de interface (prefixo de rotas no front Nuxt).
 *
 * Cargos de colaborador podem ter qualquer nome (Desenvolvedor, RH, …).
 * Apenas estes nomes de cargo reservam perfil elevado (comparação exata, case-insensitive):
 *   - Gestor  → gestor
 *   - Admin ou Administrador → admin
 */
enum AppProfile: string
{
    case Colaborador = 'colaborador';
    case Gestor = 'gestor';
    case Admin = 'admin';

    /** Nome canônico do cargo Gestor no banco. */
    public const CARGO_GESTOR = 'Gestor';

    /** Nomes aceitos para cargo Admin no banco. */
    public const CARGO_ADMIN_NAMES = ['Admin', 'Administrador'];

    public function label(): string
    {
        return match ($this) {
            self::Colaborador => 'Colaborador',
            self::Gestor => 'Gestor',
            self::Admin => 'Administrador',
        };
    }

    public static function fromCargoName(?string $cargoNome): self
    {
        $normalized = self::normalizeCargoName($cargoNome);

        return match ($normalized) {
            'gestor' => self::Gestor,
            'admin', 'administrador' => self::Admin,
            default => self::Colaborador,
        };
    }

    /**
     * Indica se o nome de cargo é reservado para Gestor ou Admin.
     */
    public static function isReservedCargoName(?string $cargoNome): bool
    {
        return self::fromCargoName($cargoNome) !== self::Colaborador;
    }

    private static function normalizeCargoName(?string $cargoNome): string
    {
        return mb_strtolower(trim($cargoNome ?? ''));
    }
}
