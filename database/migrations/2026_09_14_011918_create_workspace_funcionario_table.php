<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_funcionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->onDelete('cascade');
            $table->unsignedInteger('matricula_funcionario');
            $table->foreign('matricula_funcionario')->references('matricula_funcionario')->on('funcionario')->onDelete('cascade');
            $table->string('role');
            $table->timestamps();
            $table->unique(['workspace_id', 'matricula_funcionario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_funcionario');
    }
};
