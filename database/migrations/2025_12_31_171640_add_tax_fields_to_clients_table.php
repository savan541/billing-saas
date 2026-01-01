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
            $table->string('tax_id')->nullable()->after('email'); // VAT/GST/Tax ID
            $table->string('tax_country')->nullable()->after('tax_id'); // Country code
            $table->string('tax_state')->nullable()->after('tax_country'); // State/Province
            $table->decimal('tax_rate', 5, 4)->default(0.00)->after('tax_state'); // Tax rate (e.g., 0.0825 for 8.25%)
            $table->boolean('tax_exempt')->default(false)->after('tax_rate'); // Tax exempt status
            $table->text('tax_exemption_reason')->nullable()->after('tax_exempt'); // Reason for exemption
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['tax_id', 'tax_country', 'tax_state', 'tax_rate', 'tax_exempt', 'tax_exemption_reason']);
        });
    }
};
