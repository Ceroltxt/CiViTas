<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_tarefa', function (Blueprint $table) {
            $table->increments('ID_status_tarefa');
            $table->string('nome_status')->unique();
            $table->timestamps();
        });

        Schema::create('projeto', function (Blueprint $table) {
            $table->increments('ID_projeto');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('prioridade')->default('media');
            $table->dateTime('data_inicio')->nullable();
            $table->dateTime('data_previsao_fim')->nullable();
            $table->dateTime('data_conclusao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('ID_matricula_admin')->nullable();
            $table->timestamps();

            $table->foreign('ID_matricula_admin')
                ->references('matricula_funcionario')
                ->on('funcionario')
                ->nullOnDelete();
        });

        Schema::create('equipe', function (Blueprint $table) {
            $table->increments('ID_equipe');
            $table->string('nome');
            $table->unsignedInteger('pontos_totais')->default(0);
            $table->unsignedInteger('matricula_gestor')->nullable();
            $table->timestamps();

            $table->foreign('matricula_gestor')
                ->references('matricula_funcionario')
                ->on('funcionario')
                ->nullOnDelete();
        });

        Schema::create('equipe_funcionario', function (Blueprint $table) {
            $table->unsignedInteger('ID_equipe');
            $table->unsignedInteger('matricula_funcionario');
            $table->timestamps();

            $table->primary(['ID_equipe', 'matricula_funcionario']);
            $table->foreign('ID_equipe')->references('ID_equipe')->on('equipe')->cascadeOnDelete();
            $table->foreign('matricula_funcionario')->references('matricula_funcionario')->on('funcionario')->cascadeOnDelete();
        });

        Schema::create('projeto_equipe', function (Blueprint $table) {
            $table->unsignedInteger('ID_projeto');
            $table->unsignedInteger('ID_equipe');
            $table->timestamps();

            $table->primary(['ID_projeto', 'ID_equipe']);
            $table->foreign('ID_projeto')->references('ID_projeto')->on('projeto')->cascadeOnDelete();
            $table->foreign('ID_equipe')->references('ID_equipe')->on('equipe')->cascadeOnDelete();
        });

        Schema::create('tarefa', function (Blueprint $table) {
            $table->increments('ID_tarefa');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedInteger('pontos_base')->default(0);
            $table->string('prioridade')->default('media');
            $table->date('data_inicio')->nullable();
            $table->date('data_prazo')->nullable();
            $table->date('data_conclusao')->nullable();
            $table->boolean('pessoal')->default(false);
            $table->string('tipo')->nullable();
            $table->string('complexidade')->nullable();
            $table->string('categoria')->nullable();
            $table->string('programa')->nullable();
            $table->unsignedInteger('matricula_gestor')->nullable();
            $table->unsignedInteger('ID_projeto')->nullable();
            $table->unsignedInteger('ID_equipe')->nullable();
            $table->unsignedInteger('ID_status_tarefa')->nullable();
            $table->timestamps();

            $table->foreign('matricula_gestor')->references('matricula_funcionario')->on('funcionario')->nullOnDelete();
            $table->foreign('ID_projeto')->references('ID_projeto')->on('projeto')->nullOnDelete();
            $table->foreign('ID_equipe')->references('ID_equipe')->on('equipe')->nullOnDelete();
            $table->foreign('ID_status_tarefa')->references('ID_status_tarefa')->on('status_tarefa')->nullOnDelete();
        });

        Schema::create('tarefa_funcionario', function (Blueprint $table) {
            $table->unsignedInteger('ID_tarefa');
            $table->unsignedInteger('matricula_colaborador');
            $table->timestamps();

            $table->primary(['ID_tarefa', 'matricula_colaborador']);
            $table->foreign('ID_tarefa')->references('ID_tarefa')->on('tarefa')->cascadeOnDelete();
            $table->foreign('matricula_colaborador')->references('matricula_funcionario')->on('funcionario')->cascadeOnDelete();
        });

        Schema::create('subtarefa', function (Blueprint $table) {
            $table->increments('ID_subtarefa');
            $table->string('nome');
            $table->boolean('concluida')->default(false);
            $table->date('data_prazo')->nullable();
            $table->unsignedInteger('ID_tarefa');
            $table->unsignedInteger('matricula_colaborador')->nullable();
            $table->timestamps();

            $table->foreign('ID_tarefa')->references('ID_tarefa')->on('tarefa')->cascadeOnDelete();
            $table->foreign('matricula_colaborador')->references('matricula_funcionario')->on('funcionario')->nullOnDelete();
        });

        Schema::create('historico_tarefa', function (Blueprint $table) {
            $table->increments('ID_historico');
            $table->string('acao');
            $table->text('detalhes')->nullable();
            $table->unsignedInteger('ID_tarefa');
            $table->unsignedInteger('matricula_funcionario')->nullable();
            $table->timestamps();

            $table->foreign('ID_tarefa')->references('ID_tarefa')->on('tarefa')->cascadeOnDelete();
            $table->foreign('matricula_funcionario')->references('matricula_funcionario')->on('funcionario')->nullOnDelete();
        });

        Schema::create('anexo', function (Blueprint $table) {
            $table->increments('ID_anexo');
            $table->string('nome');
            $table->string('url');
            $table->string('tipo')->nullable();
            $table->unsignedInteger('ID_tarefa');
            $table->timestamps();

            $table->foreign('ID_tarefa')->references('ID_tarefa')->on('tarefa')->cascadeOnDelete();
        });

        Schema::create('pontos_usuario', function (Blueprint $table) {
            $table->increments('ID_pontos');
            $table->unsignedInteger('pontos');
            $table->string('acao');
            $table->dateTime('data');
            $table->unsignedInteger('matricula_funcionario');
            $table->unsignedInteger('ID_tarefa')->nullable();
            $table->timestamps();

            $table->foreign('matricula_funcionario')->references('matricula_funcionario')->on('funcionario')->cascadeOnDelete();
            $table->foreign('ID_tarefa')->references('ID_tarefa')->on('tarefa')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pontos_usuario');
        Schema::dropIfExists('anexo');
        Schema::dropIfExists('historico_tarefa');
        Schema::dropIfExists('subtarefa');
        Schema::dropIfExists('tarefa_funcionario');
        Schema::dropIfExists('tarefa');
        Schema::dropIfExists('projeto_equipe');
        Schema::dropIfExists('equipe_funcionario');
        Schema::dropIfExists('equipe');
        Schema::dropIfExists('projeto');
        Schema::dropIfExists('status_tarefa');
    }
};
