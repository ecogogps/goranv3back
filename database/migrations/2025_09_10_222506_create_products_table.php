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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('woocommerce_id')->unique(); // ID original de WooCommerce
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type')->nullable(); // Ej. simple, variable
            $table->string('status')->nullable(); // Ej. publish, draft
            $table->decimal('price', 8, 2)->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable(); // Descripción completa, si se necesita
            $table->json('images')->nullable(); // Almacenar array de URLs de imágenes
            $table->timestamps();
        });

        // Tabla pivote para la relación muchos a muchos entre productos y categorías
        Schema::create('product_product_category', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_category_id')->constrained('product_categories')->onDelete('cascade');
            $table->primary(['product_id', 'product_category_id'], 'product_category_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_category');
        Schema::dropIfExists('products');
    }
};
