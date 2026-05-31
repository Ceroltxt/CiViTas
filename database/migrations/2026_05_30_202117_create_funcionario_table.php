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
            $table->integer('pontos_totats')->default(0);

            $table->string('senha');

            $table->timestamps();
            //chaves estrangeirass
            $table->foreignId('ID_departamento')->constrained('departamento', 'ID_departamento')->onDelete('set null');

            $table->foreignId('ID_cargo')->constrained('cargo', 'ID_cargo')->onDelete('set null');

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
