<?php

namespace App\Application\Identity\Auth;

use App\Events\Identity\FuncionarioRegistered;
use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use Illuminate\Support\Facades\DB;

class RegisterAction
{
    /**
     * @return array{funcionario: Funcionario}
     */
    public function execute(
        string $nome,
        string $email,
        string $password,
        ?string $sobrenome = null,
        ?string $nomeDepartamento = null,
        ?string $cpf = null
    ): array {
        return DB::transaction(function () use ($nome, $email, $password, $sobrenome, $nomeDepartamento, $cpf) {
            $cargoDefault = Cargo::query()->firstOrCreate(['nome_cargo' => 'Admin']);
            $departamentoDefault = Departamento::query()->firstOrCreate([
                'nome_departamento' => $nomeDepartamento ?: 'Geral',
            ]);

            $parts = explode(' ', trim($nome), 2);
            $primeiroNome = $parts[0];
            $sobrenomeCalc = $sobrenome ?: ($parts[1] ?? 'Admin');

            $funcionario = Funcionario::query()->create([
                'nome' => $primeiroNome,
                'sobrenome' => $sobrenomeCalc,
                'email' => $email,
                'CPF' => $cpf ?: null,
                'data_nascimento' => '2000-01-01',
                'pontos_totais' => 0,
                'senha' => $password,
                'ID_departamento' => $departamentoDefault->getKey(),
                'ID_cargo' => $cargoDefault->getKey(),
            ]);

            // Criar workspace pessoal para o novo usuário com papel de admin
            $workspace = \App\Models\Workspace\Workspace::query()->create([
                'nome' => 'Workspace de ' . $primeiroNome,
            ]);

            DB::table('workspace_funcionario')->insert([
                'workspace_id' => $workspace->id,
                'matricula_funcionario' => $funcionario->matricula_funcionario,
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Criar tarefa pessoal de boas-vindas para o primeiro acesso
            $statusAFazer = StatusTarefa::query()->where('nome_status', 'a-fazer')->first();

            if ($statusAFazer) {
                Tarefa::query()->create([
                    'nome' => 'Boas-vindas ao CiViTas! Explore o seu painel',
                    'descricao' => 'Conheça suas tarefas, visualize o quadro e acompanhe seu progresso.',
                    'pontos_base' => 50,
                    'prioridade' => 'alta',
                    'data_inicio' => now(),
                    'data_prazo' => now()->addDays(7),
                    'pessoal' => DB::connection()->getDriverName() === 'pgsql' ? DB::raw('true') : true,
                    'matricula_gestor' => $funcionario->matricula_funcionario,
                    'ID_status_tarefa' => $statusAFazer->ID_status_tarefa,
                ]);
            }

            // Dispara o evento após confirmação da transação no banco de dados
            DB::afterCommit(function () use ($funcionario) {
                FuncionarioRegistered::dispatch($funcionario);
            });

            return [
                'funcionario' => $funcionario->load(['departamento', 'cargo']),
            ];
        });
    }
}
