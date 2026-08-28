<?php

namespace Database\Seeders;

use App\Domain\Authorization\AppProfile;
use App\Domain\Authorization\Permissions;
use App\Models\Authorization\Permissao;
use App\Models\Organization\Cargo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::all() as $slug) {
            Permissao::query()->firstOrCreate(['nome_permissao' => $slug]);
        }

        // Cargos reservados — nomes fixos para Gestor e Admin.
        Cargo::query()->firstOrCreate(['nome_cargo' => AppProfile::CARGO_GESTOR]);

        foreach (AppProfile::CARGO_ADMIN_NAMES as $adminCargoName) {
            Cargo::query()->firstOrCreate(['nome_cargo' => $adminCargoName]);
        }

        // Exemplos de cargos operacionais (perfil Colaborador, nomes livres).
        foreach (['Desenvolvedor', 'RH'] as $operationalCargo) {
            Cargo::query()->firstOrCreate(['nome_cargo' => $operationalCargo]);
        }

        Cargo::query()->each(function (Cargo $cargo): void {
            $permissions = match (AppProfile::fromCargoName($cargo->nome_cargo)) {
                AppProfile::Admin => Permissions::forAdmin(),
                AppProfile::Gestor => Permissions::forGestor(),
                AppProfile::Colaborador => Permissions::forColaborador(),
            };

            $this->syncCargoPermissions($cargo, $permissions);
        });
    }

    /**
     * @param  list<string>  $slugs
     */
    private function syncCargoPermissions(Cargo $cargo, array $slugs): void
    {
        $permissionIds = Permissao::query()
            ->whereIn('nome_permissao', $slugs)
            ->pluck('ID_permissao', 'nome_permissao');

        foreach ($slugs as $slug) {
            $permissionId = $permissionIds[$slug] ?? null;

            if ($permissionId === null) {
                continue;
            }

            DB::table('permissao_cargo')->updateOrInsert(
                [
                    'ID_cargo' => $cargo->ID_cargo,
                    'ID_permissao' => $permissionId,
                ],
                [
                    'ativo' => true,
                    'data_atribuicao' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
