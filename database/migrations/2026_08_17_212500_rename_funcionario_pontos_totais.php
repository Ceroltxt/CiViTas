<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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
          AND column_name = 'pontos_totais'
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'funcionario'
          AND column_name = 'pontos_totats'
    ) THEN
        ALTER TABLE public.funcionario RENAME COLUMN pontos_totais TO pontos_totats;
    END IF;
END $$;
SQL);
    }
};
