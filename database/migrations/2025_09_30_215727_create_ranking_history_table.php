<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->integer('ranking'); // Ranking después del cambio
            $table->integer('previous_ranking'); // Ranking antes del cambio
            $table->integer('change'); // Diferencia (+8, -10, etc.)
            $table->foreignId('game_id')->nullable()->constrained('games')->onDelete('set null');
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->onDelete('set null');
            $table->string('reason')->default('game_result'); // game_result, manual_adjustment, etc.
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('member_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_history');
    }
};
