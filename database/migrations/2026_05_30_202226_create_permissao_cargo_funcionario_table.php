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
        Schema::create('permissao_cargo_funcionario', function (Blueprint $table) {
            $table->unsignedInteger('ID_CP');
            $table->unsignedInteger('matricula_funcionario');
            $table->timestamps();

            $table->primary(['ID_CP', 'matricula_funcionario']);

            $table->foreign('ID_CP')
                ->references('ID_CP')
                ->on('permissao_cargo')
                ->cascadeOnDelete();

            $table->foreign('matricula_funcionario')
                ->references('matricula_funcionario')
                ->on('funcionario')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissao_cargo_funcionario');
    }
};
