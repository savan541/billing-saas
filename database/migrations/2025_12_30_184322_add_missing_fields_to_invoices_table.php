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
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number')->after('client_id');
            }
            
            if (!Schema::hasColumn('invoices', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('tax');
            }
            
            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->after('total');
            }
            
            if (!Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->after('issue_date');
            }
            
            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('due_date');
            }
            
            // Update existing columns if needed
            if (Schema::hasColumn('invoices', 'number')) {
                $table->dropColumn('number');
            }
            
            if (Schema::hasColumn('invoices', 'issued_at')) {
                $table->dropColumn('issued_at');
            }
            
            if (Schema::hasColumn('invoices', 'due_at')) {
                $table->dropColumn('due_at');
            }
            
            // Add unique constraint for invoice_number per user
            if (!Schema::hasIndex('invoices', ['user_id', 'invoice_number'])) {
                $table->unique(['user_id', 'invoice_number']);
            }
            
            // Add due_date index if it doesn't exist
            if (!Schema::hasIndex('invoices', 'due_date')) {
                $table->index('due_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the new columns
            if (Schema::hasColumn('invoices', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
            
            if (Schema::hasColumn('invoices', 'discount')) {
                $table->dropColumn('discount');
            }
            
            if (Schema::hasColumn('invoices', 'issue_date')) {
                $table->dropColumn('issue_date');
            }
            
            if (Schema::hasColumn('invoices', 'due_date')) {
                $table->dropColumn('due_date');
            }
            
            if (Schema::hasColumn('invoices', 'notes')) {
                $table->dropColumn('notes');
            }
            
            // Add back the old columns
            $table->string('number')->after('client_id');
            $table->date('issued_at')->after('total');
            $table->date('due_at')->after('issued_at');
        });
    }
};
