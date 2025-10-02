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
        Schema::table('clubs', function (Blueprint $table) {
            // Información general del club
            $table->string('ruc', 20)->nullable()->after('liga_id');
            $table->string('pais', 100)->nullable()->after('ruc');
            $table->string('provincia', 100)->nullable()->after('pais');
            $table->string('ciudad', 100)->nullable()->after('provincia');
            $table->string('direccion')->nullable()->after('ciudad');
            $table->string('celular', 20)->nullable()->after('direccion');
            $table->string('google_maps_url', 500)->nullable()->after('celular');
            
            // Información del representante
            $table->string('representante_nombre')->nullable()->after('google_maps_url');
            $table->string('representante_telefono', 20)->nullable()->after('representante_nombre');
            $table->string('representante_email')->nullable()->after('representante_telefono');
            
            // Información del administrador 1
            $table->string('admin1_nombre')->nullable()->after('representante_email');
            $table->string('admin1_telefono', 20)->nullable()->after('admin1_nombre');
            $table->string('admin1_email')->nullable()->after('admin1_telefono');
            
            // Información del administrador 2
            $table->string('admin2_nombre')->nullable()->after('admin1_email');
            $table->string('admin2_telefono', 20)->nullable()->after('admin2_nombre');
            $table->string('admin2_email')->nullable()->after('admin2_telefono');
            
            // Información del administrador 3
            $table->string('admin3_nombre')->nullable()->after('admin2_email');
            $table->string('admin3_telefono', 20)->nullable()->after('admin3_nombre');
            $table->string('admin3_email')->nullable()->after('admin3_telefono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'ruc',
                'pais',
                'provincia',
                'ciudad',
                'direccion',
                'celular',
                'google_maps_url',
                'representante_nombre',
                'representante_telefono',
                'representante_email',
                'admin1_nombre',
                'admin1_telefono',
                'admin1_email',
                'admin2_nombre',
                'admin2_telefono',
                'admin2_email',
                'admin3_nombre',
                'admin3_telefono',
                'admin3_email'
            ]);
        });
    }
};
