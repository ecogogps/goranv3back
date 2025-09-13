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
        Schema::create('product_product_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_status_id')->constrained()->onDelete('cascade');
            
            $table->integer('stock_quantity')->default(0);
            
            // Para 'Bajo Pedido'
            $table->boolean('is_own_product')->nullable();
            $table->boolean('sample_available')->nullable();

            // Para 'Preventas'
            $table->date('preventa_start_date')->nullable();
            $table->date('preventa_end_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_status');
    }
};
