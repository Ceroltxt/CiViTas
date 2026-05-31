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
            $table->foreignId('ID_CP')->constrained('permissao_cargo', 'ID_CP')->onDelete('set null');
            $table->foreignId('matricula_funcionario')->constrained('funcionario', 'matricula_funcionario')->onDelete(' set null');
            $table->timestamps();

            $table->primary(['ID_CP', 'matricula_funcionario']);
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
