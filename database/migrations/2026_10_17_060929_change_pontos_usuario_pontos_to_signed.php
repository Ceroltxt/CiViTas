
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pontos_usuario', function (Blueprint $table) {
            $table->integer('pontos')->change();
        });
    }

    public function down(): void
    {
        // Não voltar para unsigned, pois o histórico poderá
        // conter penalidades negativas.
    }
};
