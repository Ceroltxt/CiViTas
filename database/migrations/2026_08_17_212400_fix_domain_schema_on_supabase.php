<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align the existing Supabase schema with the corrected domain migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'funcionario'
          AND column_name = 'pontos_totats'
    ) THEN
        ALTER TABLE public.funcionario RENAME COLUMN pontos_totats TO pontos_totais;
    END IF;
END $$;
SQL);

        $this->dropForeignIfExists('funcionario', 'funcionario_id_departamento_foreign');
        $this->dropForeignIfExists('funcionario', 'funcionario_id_cargo_foreign');
        $this->dropForeignIfExists('permissao_cargo', 'permissao_cargo_id_cargo_foreign');
        $this->dropForeignIfExists('permissao_cargo', 'permissao_cargo_id_permissao_foreign');
        $this->dropForeignIfExists('permissao_cargo_funcionario', 'permissao_cargo_funcionario_id_cp_foreign');
        $this->dropForeignIfExists('permissao_cargo_funcionario', 'permissao_cargo_funcionario_matricula_funcionario_foreign');

        DB::statement('ALTER TABLE funcionario ALTER COLUMN "ID_departamento" TYPE integer USING "ID_departamento"::integer');
        DB::statement('ALTER TABLE funcionario ALTER COLUMN "ID_cargo" TYPE integer USING "ID_cargo"::integer');
        DB::statement('ALTER TABLE funcionario ALTER COLUMN "ID_departamento" DROP NOT NULL');
        DB::statement('ALTER TABLE funcionario ALTER COLUMN "ID_cargo" DROP NOT NULL');

        DB::statement('ALTER TABLE permissao_cargo ALTER COLUMN "ID_cargo" TYPE integer USING "ID_cargo"::integer');
        DB::statement('ALTER TABLE permissao_cargo ALTER COLUMN "ID_permissao" TYPE integer USING "ID_permissao"::integer');

        DB::statement('ALTER TABLE permissao_cargo_funcionario ALTER COLUMN "ID_CP" TYPE integer USING "ID_CP"::integer');
        DB::statement('ALTER TABLE permissao_cargo_funcionario ALTER COLUMN matricula_funcionario TYPE integer USING matricula_funcionario::integer');

        Schema::table('funcionario', function (Blueprint $table) {
            $table->foreign('ID_departamento')
                ->references('ID_departamento')
                ->on('departamento')
                ->nullOnDelete();

            $table->foreign('ID_cargo')
                ->references('ID_cargo')
                ->on('cargo')
                ->nullOnDelete();
        });

        Schema::table('permissao_cargo', function (Blueprint $table) {
            $table->foreign('ID_cargo')
                ->references('ID_cargo')
                ->on('cargo')
                ->cascadeOnDelete();

            $table->foreign('ID_permissao')
                ->references('ID_permissao')
                ->on('permissoes')
                ->cascadeOnDelete();
        });

        Schema::table('permissao_cargo_funcionario', function (Blueprint $table) {
            $table->foreign('ID_CP')
                ->references('ID_CP')
                ->on('permissao_cargo')
                ->cascadeOnDelete();

            $table->foreign('matricula_funcionario')
                ->references('matricula_funcionario')
                ->on('funcionario')
                ->cascadeOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS permissao_cargo_id_cargo_id_permissao_unique ON permissao_cargo ("ID_cargo", "ID_permissao")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data-definition repair for the live Supabase schema.
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            'select 1 as present from pg_constraint where conname = ?',
            [$constraint],
        );

        if ($exists === null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
            $blueprint->dropForeign($constraint);
        });
    }
};
