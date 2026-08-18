<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('funcionario', function (Blueprint $table) {
            $table->increments('matricula_funcionario');
            $table->string('nome');
            $table->string('sobrenome');
            $table->date('data_nascimento');
            $table->string('email')->unique();
            $table->string('CPF')->unique();
            $table->unsignedInteger('pontos_totais')->default(0);
            $table->string('senha');
            $table->timestamps();

            $table->unsignedInteger('ID_departamento')->nullable();
            $table->unsignedInteger('ID_cargo')->nullable();

            $table->foreign('ID_departamento')
                ->references('ID_departamento')
                ->on('departamento')
                ->nullOnDelete();

            $table->foreign('ID_cargo')
                ->references('ID_cargo')
                ->on('cargo')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionario');
    }
};
