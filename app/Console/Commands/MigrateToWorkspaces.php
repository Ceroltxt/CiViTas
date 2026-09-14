<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Workspace\Workspace;
use App\Models\Identity\Funcionario;
use App\Models\Team\Equipe;
use Illuminate\Support\Facades\DB;
use App\Domain\Authorization\AppProfile;

class MigrateToWorkspaces extends Command
{
    protected $signature = 'app:migrate-to-workspaces';
    protected $description = 'Migra os dados atuais (sem workspace) para um Workspace Padrão';

    public function handle()
    {
        $this->info('Iniciando migração de dados para Workspaces...');

        DB::transaction(function () {
            // 1. Criar Workspace Padrão
            $workspace = Workspace::firstOrCreate(
                ['nome' => 'Workspace Padrão']
            );
            $this->info("Workspace 'Workspace Padrão' (ID: {$workspace->id}) criado/encontrado.");

            // 2. Vincular todos os usuários existentes ao Workspace Padrão
            $funcionarios = Funcionario::with('cargo')->get();
            $countUsers = 0;

            foreach ($funcionarios as $f) {
                // Determina o papel atual com base no cargo legado
                $legacyProfile = $f->profile(); 
                $role = match ($legacyProfile) {
                    AppProfile::Admin => 'admin',
                    AppProfile::Gestor => 'gestor',
                    default => 'colaborador',
                };

                // Insere no pivot se não existir
                $exists = DB::table('workspace_funcionario')
                    ->where('workspace_id', $workspace->id)
                    ->where('matricula_funcionario', $f->matricula_funcionario)
                    ->exists();

                if (!$exists) {
                    DB::table('workspace_funcionario')->insert([
                        'workspace_id' => $workspace->id,
                        'matricula_funcionario' => $f->matricula_funcionario,
                        'role' => $role,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $countUsers++;
                }
            }
            $this->info("{$countUsers} usuários vinculados ao Workspace.");

            // 3. Vincular todos os quadros (equipes) ao Workspace Padrão
            $equipesAtualizadas = Equipe::whereNull('workspace_id')
                ->update(['workspace_id' => $workspace->id]);
                
            $this->info("{$equipesAtualizadas} quadros (equipes) atualizados para o Workspace Padrão.");
        });

        $this->info('Migração concluída com sucesso!');
    }
}
