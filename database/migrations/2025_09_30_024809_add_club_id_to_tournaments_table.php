<?php
// Ejecuta: php artisan make:migration add_club_id_to_tournaments_table
// database/migrations/XXXX_XX_XX_add_club_id_to_tournaments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // Agregar club_id DESPUÉS de una columna existente
            $table->foreignId('club_id')
                  ->nullable()
                  ->after('id') // Lo coloca después del id
                  ->constrained('clubs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });
    }
};
