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
            $table->decimal('stock', 15, 3)->default(0)->change();
            $table->decimal('min_stock', 15, 3)->default(5)->change();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->default(1)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('qty_in', 15, 3)->default(0)->change();
            $table->decimal('qty_out', 15, 3)->default(0)->change();
            $table->decimal('stock_before', 15, 3)->default(0)->change();
            $table->decimal('stock_after', 15, 3)->default(0)->change();
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->default(1)->change();
        });

        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->default(1)->change();
        });

        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->decimal('qty', 15, 3)->default(1)->change();
        });

        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->decimal('system_stock', 15, 3)->default(0)->change();
            $table->decimal('physical_stock', 15, 3)->default(0)->change();
            $table->decimal('difference', 15, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
            $table->integer('min_stock')->default(5)->change();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->integer('qty')->default(1)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->integer('qty_in')->default(0)->change();
            $table->integer('qty_out')->default(0)->change();
            $table->integer('stock_before')->default(0)->change();
            $table->integer('stock_after')->default(0)->change();
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->integer('qty')->default(1)->change();
        });

        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->integer('qty')->default(1)->change();
        });

        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->integer('qty')->default(1)->change();
        });

        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->integer('system_stock')->default(0)->change();
            $table->integer('physical_stock')->default(0)->change();
            $table->integer('difference')->default(0)->change();
        });
    }
};
