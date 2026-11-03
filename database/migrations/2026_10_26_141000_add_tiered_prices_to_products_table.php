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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_silver', 15, 2)->nullable()->default(null)->after('selling_price');
            $table->decimal('price_gold', 15, 2)->nullable()->default(null)->after('price_silver');
            $table->decimal('price_platinum', 15, 2)->nullable()->default(null)->after('price_gold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_silver', 'price_gold', 'price_platinum']);
        });
    }
};
