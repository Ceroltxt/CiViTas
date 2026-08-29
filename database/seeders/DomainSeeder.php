<?php

namespace Database\Seeders;

use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use App\Models\Project\Projeto;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Subtarefa;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use App\Domain\Authorization\AppProfile;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'a-fazer',
            'em-andamento',
            'em-revisao',
            'validar',
            'bloqueado',
            'atrasado',
            'concluido',
            'pausado',
            'cancelado',
        ];

        foreach ($statuses as $status) {
            StatusTarefa::query()->firstOrCreate(['nome_status' => $status]);
        }

        $departamento = Departamento::query()->firstOrCreate(['nome_departamento' => 'Engenharia']);
        $cargoColaborador = Cargo::query()->firstOrCreate(['nome_cargo' => 'Desenvolvedor']);
        $cargoGestor = Cargo::query()->firstOrCreate(['nome_cargo' => AppProfile::CARGO_GESTOR]);
        $cargoAdmin = Cargo::query()->firstOrCreate(['nome_cargo' => 'Admin']);

        $colaborador = Funcionario::query()->updateOrCreate(
            ['email' => 'ana@civitas.test'],
            [
                'nome' => 'Costa',
                'sobrenome' => 'Neves',
                'data_nascimento' => '1995-04-12',
                'CPF' => '12345678901',
                'pontos_totais' => 12400,
                'senha' => 'secret-password',
                'ID_departamento' => $departamento->getKey(),
                'ID_cargo' => $cargoColaborador->getKey(),
            ],
        );

        $gestor = Funcionario::query()->updateOrCreate(
            ['email' => 'gestor@civitas.test'],
            [
                'nome' => 'Milani',
                'sobrenome' => 'Ribeiro',
                'data_nascimento' => '1990-01-15',
                'CPF' => '98765432100',
                'pontos_totais' => 23150,
                'senha' => 'secret-password',
                'ID_departamento' => $departamento->getKey(),
                'ID_cargo' => $cargoGestor->getKey(),
            ],
        );

        Funcionario::query()->updateOrCreate(
            ['email' => 'admin@civitas.test'],
            [
                'nome' => 'Roberto',
                'sobrenome' => 'Admin',
                'data_nascimento' => '1988-06-20',
                'CPF' => '11122233344',
                'pontos_totais' => 27800,
                'senha' => 'secret-password',
                'ID_departamento' => $departamento->getKey(),
                'ID_cargo' => $cargoAdmin->getKey(),
            ],
        );

        Funcionario::query()->updateOrCreate(
            ['email' => 'juliana@civitas.test'],
            [
                'nome' => 'Juliana',
                'sobrenome' => 'Costa',
                'data_nascimento' => '1992-03-10',
                'CPF' => '55566677788',
                'pontos_totais' => 27800,
                'senha' => 'secret-password',
                'ID_departamento' => $departamento->getKey(),
                'ID_cargo' => $cargoColaborador->getKey(),
            ],
        );

        $projeto = Projeto::query()->updateOrCreate(
            ['nome' => 'Nova Praça Central'],
            [
                'descricao' => 'Construção e fiscalização da nova praça central.',
                'prioridade' => 'alta',
                'data_inicio' => now()->subMonths(3),
                'data_previsao_fim' => now()->addMonths(6),
                'ativo' => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? \Illuminate\Support\Facades\DB::raw('true') : true,
                'ID_matricula_admin' => $gestor->matricula_funcionario,
            ],
        );

        $equipe = Equipe::query()->updateOrCreate(
            ['nome' => 'Obras e Infraestrutura'],
            [
                'pontos_totais' => 500,
                'matricula_gestor' => $gestor->matricula_funcionario,
            ],
        );

        $equipe->membros()->syncWithoutDetaching([$colaborador->matricula_funcionario]);
        $projeto->equipes()->syncWithoutDetaching([$equipe->ID_equipe]);

        $statusAFazer = StatusTarefa::query()->where('nome_status', 'a-fazer')->firstOrFail();
        $statusAndamento = StatusTarefa::query()->where('nome_status', 'em-andamento')->firstOrFail();
        $statusConcluido = StatusTarefa::query()->where('nome_status', 'concluido')->firstOrFail();
        $statusAtrasado = StatusTarefa::query()->where('nome_status', 'atrasado')->firstOrFail();

        $tarefa = Tarefa::query()->updateOrCreate(
            ['nome' => 'Fiscalizar obra da Nova Praça Central'],
            [
                'descricao' => 'Fiscalizar o andamento da obra, verificando materiais e medição.',
                'pontos_base' => 200,
                'prioridade' => 'alta',
                'data_inicio' => now()->subDays(10),
                'data_prazo' => now()->addDays(15),
                'pessoal' => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? \Illuminate\Support\Facades\DB::raw('false') : false,
                'tipo' => 'Fiscalização',
                'complexidade' => 'Alta',
                'categoria' => 'Obras',
                'programa' => 'Mais Praças',
                'matricula_gestor' => $gestor->matricula_funcionario,
                'ID_projeto' => $projeto->ID_projeto,
                'ID_equipe' => $equipe->ID_equipe,
                'ID_status_tarefa' => $statusAFazer->ID_status_tarefa,
            ],
        );

        $tarefa->colaboradores()->syncWithoutDetaching([$colaborador->matricula_funcionario]);

        Subtarefa::query()->updateOrCreate(
            ['nome' => 'Verificar alicerces', 'ID_tarefa' => $tarefa->ID_tarefa],
            [
                'concluida' => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? \Illuminate\Support\Facades\DB::raw('false') : false,
                'data_prazo' => now()->addDays(5),
                'matricula_colaborador' => $colaborador->matricula_funcionario,
            ],
        );

        HistoricoTarefa::query()->create([
            'acao' => 'Tarefa criada',
            'detalhes' => $tarefa->nome,
            'ID_tarefa' => $tarefa->ID_tarefa,
            'matricula_funcionario' => $gestor->matricula_funcionario,
        ]);

        Tarefa::query()->updateOrCreate(
            ['nome' => 'Agendar férias'],
            [
                'descricao' => 'Solicitar férias no RH.',
                'pontos_base' => 0,
                'prioridade' => 'alta',
                'data_prazo' => now()->addMonth(),
                'pessoal' => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? \Illuminate\Support\Facades\DB::raw('true') : true,
                'matricula_gestor' => $colaborador->matricula_funcionario,
                'ID_status_tarefa' => $statusAFazer->ID_status_tarefa,
            ],
        );

        Tarefa::query()->updateOrCreate(
            ['nome' => 'Cotar peças para ônibus'],
            [
                'descricao' => 'Cotação de peças para frota municipal.',
                'pontos_base' => 100,
                'prioridade' => 'media',
                'data_inicio' => now()->subDays(20),
                'data_prazo' => now()->addDays(30),
                'matricula_gestor' => $gestor->matricula_funcionario,
                'ID_projeto' => $projeto->ID_projeto,
                'ID_equipe' => $equipe->ID_equipe,
                'ID_status_tarefa' => $statusAndamento->ID_status_tarefa,
            ],
        )->colaboradores()->syncWithoutDetaching([$colaborador->matricula_funcionario]);

        Tarefa::query()->updateOrCreate(
            ['nome' => 'Auditar processos atrasados de alvarás'],
            [
                'descricao' => 'Verificar processos parados.',
                'pontos_base' => 150,
                'prioridade' => 'alta',
                'data_inicio' => now()->subDays(30),
                'data_prazo' => now()->subDays(7),
                'matricula_gestor' => $gestor->matricula_funcionario,
                'ID_projeto' => $projeto->ID_projeto,
                'ID_equipe' => $equipe->ID_equipe,
                'ID_status_tarefa' => $statusAtrasado->ID_status_tarefa,
            ],
        )->colaboradores()->syncWithoutDetaching([$colaborador->matricula_funcionario]);

        Tarefa::query()->updateOrCreate(
            ['nome' => 'Aprovar projeto de novos postes'],
            [
                'descricao' => 'Aprovação de expansão de iluminação.',
                'pontos_base' => 300,
                'prioridade' => 'baixa',
                'data_inicio' => now()->subDays(15),
                'data_prazo' => now()->subDays(5),
                'data_conclusao' => now()->subDays(3),
                'matricula_gestor' => $gestor->matricula_funcionario,
                'ID_projeto' => $projeto->ID_projeto,
                'ID_equipe' => $equipe->ID_equipe,
                'ID_status_tarefa' => $statusConcluido->ID_status_tarefa,
            ],
        )->colaboradores()->syncWithoutDetaching([$colaborador->matricula_funcionario]);
    }
}
