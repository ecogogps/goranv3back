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
        // 1. Modificar la tabla 'users' existente para añadir campos de rol y estado
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
        
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role'); // Ej: 'liga', 'club', 'miembro', 'superadmin'
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        // 2. Modificar las tablas de perfiles para que se relacionen con 'users'
        Schema::table('ligas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            if (Schema::hasColumn('ligas', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('ligas', 'password')) {
                $table->dropColumn('password');
            }
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
             // Asumimos que club no tenía email/password, pero si los tuviera, se eliminarían aquí.
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            if (Schema::hasColumn('members', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('members', 'password')) {
                $table->dropColumn('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
