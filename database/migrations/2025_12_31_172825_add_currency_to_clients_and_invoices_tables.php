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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('tax_exemption_reason');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('tax_exempt_at_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
