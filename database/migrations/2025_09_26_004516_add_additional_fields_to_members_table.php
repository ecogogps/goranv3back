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
        Schema::table('members', function (Blueprint $table) {
            // Información personal
            $table->string('cedula')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['Masculino', 'Femenino', 'Otro'])->nullable();
            $table->string('pais')->nullable();
            $table->string('provincia')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('email')->nullable();
            
            // Caucho del drive
            $table->string('drive_marca')->nullable();
            $table->string('drive_modelo')->nullable();
            $table->enum('drive_tipo', ['Antitopsping', 'Liso', 'Pupo Corto', 'Pupo Largo', 'Todos'])->nullable();
            
            // Caucho del back
            $table->string('back_marca')->nullable();
            $table->string('back_modelo')->nullable();
            $table->enum('back_tipo', ['Antitopsping', 'Liso', 'Pupo Corto', 'Pupo Largo', 'Todos'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'cedula',
                'fecha_nacimiento',
                'genero',
                'pais',
                'provincia',
                'ciudad',
                'email',
                'drive_marca',
                'drive_modelo',
                'drive_tipo',
                'back_marca',
                'back_modelo',
                'back_tipo'
            ]);
        });
    }
};
