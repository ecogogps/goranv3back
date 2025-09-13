<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductStatus;

class ProductStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Encuentra el ID del estado 'Stock'
        $stockStatus = ProductStatus::where('slug', 'stock')->first();

        // Si no existe el estado 'stock', detenemos el seeder para evitar errores.
        if (!$stockStatus) {
            $this->command->error('El estado "Stock" no fue encontrado. Asegúrate de haber ejecutado el ProductStatusSeeder primero.');
            return;
        }

        // 2. Itera sobre los IDs de producto del 832 al 850
        for ($i = 832; $i <= 850; $i++) {
            $product = Product::find($i);

            // 3. Si el producto existe, le asignamos el estado de stock
            if ($product) {
                // Usamos syncWithoutDetaching para evitar duplicados si ya existe la relación
                $product->statuses()->syncWithoutDetaching([
                    $stockStatus->id => ['stock_quantity' => 50]
                ]);
                
                $this->command->info("Estado 'Stock' asignado al producto ID: {$i}");
            } else {
                $this->command->warn("Producto con ID: {$i} no encontrado. Se omitió.");
            }
        }
        
        $this->command->info('¡Seeder de stock completado!');
    }
}
