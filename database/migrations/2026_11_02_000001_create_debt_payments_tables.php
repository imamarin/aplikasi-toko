<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'due_date')) {
                $table->date('due_date')->nullable()->after('payment_status');
            }
        });

        Schema::create('receivable_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('cash_shift_id')->nullable()->constrained('cash_shifts')->onDelete('set null');
            $table->dateTime('payment_date');
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, transfer, qris, debit
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'payment_date']);
        });

        Schema::create('receivable_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_payment_id')->constrained('receivable_payments')->onDelete('cascade');
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payable_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique()->index();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('cash_shift_id')->nullable()->constrained('cash_shifts')->onDelete('set null');
            $table->dateTime('payment_date');
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, transfer
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'payment_date']);
        });

        Schema::create('payable_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_payment_id')->constrained('payable_payments')->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_payment_allocations');
        Schema::dropIfExists('payable_payments');
        Schema::dropIfExists('receivable_payment_allocations');
        Schema::dropIfExists('receivable_payments');

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }
};

