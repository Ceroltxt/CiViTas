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

            $table->foreignId('ID_cargo')->constrained('cargo', 'ID_cargo')->onDelete('set null');
            $table->foreignId('ID_permissao')->constrained('permissoes', 'ID_permissao')->onDelete('set null');
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
