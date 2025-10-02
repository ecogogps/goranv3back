<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->foreignId('liga_id')->after('id')->constrained('ligas')->onDelete('cascade');
            $table->index(['liga_id', 'code_start', 'code_end']);
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropForeign(['liga_id']);
            $table->dropColumn('liga_id');
        });
    }
};
