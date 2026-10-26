<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('cash_shift_id')->nullable()->constrained('cash_shifts')->onDelete('set null');
            $table->dateTime('transaction_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('discount_type')->default('fixed'); // fixed / percentage
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, transfer, qris, debit, credit
            $table->string('payment_status')->default('paid'); // paid, unpaid, partial
            $table->string('status')->default('completed'); // completed, canceled, returned
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['transaction_date', 'status']);
        });

        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('product_name'); // snapshot nama produk
            $table->decimal('cost_price', 15, 2)->default(0); // snapshot HPP untuk laporan keuntungan
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('qty')->default(1);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_details');
        Schema::dropIfExists('sales');
    }
};
