<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique()->index();
            $table->string('name');
            $table->decimal('cost_price', 15, 2)->default(0); // Harga Beli / Modal
            $table->decimal('selling_price', 15, 2)->default(0); // Harga Jual
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(5);
            $table->string('image')->nullable();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
