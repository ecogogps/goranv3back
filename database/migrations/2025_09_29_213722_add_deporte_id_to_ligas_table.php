<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            // Primero agregar como nullable
            $table->foreignId('deporte_id')->nullable()->after('name')->constrained('deportes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->dropForeign(['deporte_id']);
            $table->dropColumn('deporte_id');
        });
    }
};
