<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'Stock',
            'Bajo Pedido',
            'Preventa 1',
            'Preventa 2',
            'Preventa 3',
        ];

        foreach ($statuses as $status) {
            DB::table('product_statuses')->insert([
                'name' => $status,
                'slug' => Str::slug($status),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
