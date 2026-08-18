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
        Schema::create('permissao_cargo', function (Blueprint $table) {
            $table->increments('ID_CP');
            $table->boolean('ativo')->default(true);
            $table->timestamp('data_atribuicao')->nullable();
            $table->timestamp('data_expiracao')->nullable();
            $table->timestamps();

            $table->unsignedInteger('ID_cargo');
            $table->unsignedInteger('ID_permissao');

            $table->foreign('ID_cargo')
                ->references('ID_cargo')
                ->on('cargo')
                ->cascadeOnDelete();

            $table->foreign('ID_permissao')
                ->references('ID_permissao')
                ->on('permissoes')
                ->cascadeOnDelete();

            $table->unique(['ID_cargo', 'ID_permissao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissao_cargo');
    }
};
