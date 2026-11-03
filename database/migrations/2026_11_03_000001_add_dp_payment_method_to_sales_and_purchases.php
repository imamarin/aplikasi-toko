<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'dp_payment_method')) {
                $table->string('dp_payment_method')->nullable()->after('payment_method');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'dp_payment_method')) {
                $table->string('dp_payment_method')->nullable()->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'dp_payment_method')) {
                $table->dropColumn('dp_payment_method');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'dp_payment_method')) {
                $table->dropColumn('dp_payment_method');
            }
        });
    }
};

