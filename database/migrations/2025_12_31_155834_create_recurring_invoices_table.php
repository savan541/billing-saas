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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->enum('frequency', ['monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('next_run_date');
            $table->index(['user_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
