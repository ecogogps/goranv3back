<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usar una transacción para asegurar la integridad de los datos
        DB::transaction(function () {

            // --- 1. Crear el usuario Miembro ---
            User::create([
                'email' => 'miembro@gmail.com',
                'password' => Hash::make('123456'),
                'role' => 'miembro',
                'is_active' => true,
            ]);

            // --- 2. Crear el usuario Club ---
            User::create([
                'email' => 'club@gmail.com',
                'password' => Hash::make('123456'),
                'role' => 'club',
                'is_active' => true,
            ]);
            
            // --- 3. Crear el usuario Liga ---
            User::create([
                'email' => 'liga@gmail.com',
                'password' => Hash::make('123456'),
                'role' => 'liga',
                'is_active' => true,
            ]);

        });
    }
}
