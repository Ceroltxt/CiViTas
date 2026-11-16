<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefa', function (Blueprint $table) {
            $table->unsignedInteger('quantidade_reaberturas')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tarefa', function (Blueprint $table) {
            $table->dropColumn('quantidade_reaberturas');
        });
    }
};
