<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipe', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('equipe', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};
