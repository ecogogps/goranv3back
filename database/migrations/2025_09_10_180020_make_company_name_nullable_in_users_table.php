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
        Schema::table('users', function (Blueprint $table) {
            // Esto solo funciona si la columna ya existe.
            // Si no existe, este comando fallará.
            $table->string('company_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Si quieres revertir a no-nullable, tendrías que asegurar que no haya nulos
            // Esto es más complejo, por ahora, si se hace nullable, rara vez se revierte
            // $table->string('company_name')->nullable(false)->change();
        });
    }
};
