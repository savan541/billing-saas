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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('invoice_tax_rate', 5, 4)->default(0.00)->after('stripe_payment_intent_id');
            $table->boolean('tax_exempt_at_time')->default(false)->after('invoice_tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_tax_rate', 'tax_exempt_at_time']);
        });
    }
};
