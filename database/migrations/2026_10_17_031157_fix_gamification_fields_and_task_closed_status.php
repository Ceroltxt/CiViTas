<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefa', function (Blueprint $table) {
            $table->timestamp('data_fechamento_automatico')->nullable();
            $table->boolean('foi_reaberta')->default(false);
            $table->boolean('penalidade_reabertura_aplicada')->default(false);
        });

        Schema::table('pontos_usuario', function (Blueprint $table) {
            $table->string('chave_idempotencia', 150)->nullable()->unique();
        });

        DB::table('status_tarefa')->insertOrIgnore([
            'nome_status' => 'fechada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table("status_tarefa")
            ->where("nome_status", "fechada")
            ->whereNotIn("ID_status_tarefa", DB::table("tarefa")->select("ID_status_tarefa"))
            ->delete();

        if (Schema::hasColumn("pontos_usuario", "chave_idempotencia")) {
            if (Schema::hasColumn("pontos_usuario", "chave_idempotencia")) {
                $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ?", ["pontos_usuario"]);
                foreach ($indexes as $index) {
                    if ($index->indexname === "pontos_usuario_chave_idempotencia_unique") {
                        Schema::table("pontos_usuario", function (Blueprint $table) {
                            $table->dropUnique("pontos_usuario_chave_idempotencia_unique");
                        });
                        break;
                    }
                }
                Schema::table("pontos_usuario", function (Blueprint $table) {
                    $table->dropColumn("chave_idempotencia");
                });
            }
        }

        foreach (["data_fechamento_automatico", "foi_reaberta", "penalidade_reabertura_aplicada"] as $column) {
            if (Schema::hasColumn("tarefa", $column)) {
                Schema::table("tarefa", function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
