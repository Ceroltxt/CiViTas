<?php

namespace App\Application\Identity\Auth;

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
            $cargoDefault = Cargo::query()->firstOrCreate(['nome_cargo' => 'Desenvolvedor']);
            $departamentoDefault = Departamento::query()->firstOrCreate([
                'nome_departamento' => $nomeDepartamento ?: 'Engenharia',
            ]);

            $parts = explode(' ', trim($nome), 2);
            $primeiroNome = $parts[0];
            $sobrenomeCalc = $sobrenome ?: ($parts[1] ?? 'Colaborador');

            $funcionario = Funcionario::query()->create([
                'nome' => $primeiroNome,
                'sobrenome' => $sobrenomeCalc,
                'email' => $email,
                'CPF' => $cpf ?: str_pad((string) rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT),
                'data_nascimento' => '2000-01-01',
                'pontos_totais' => 0,
                'senha' => $password,
                'ID_departamento' => $departamentoDefault->getKey(),
                'ID_cargo' => $cargoDefault->getKey(),
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

            return [
                'funcionario' => $funcionario->load(['departamento', 'cargo']),
            ];
        });
    }
}
