<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payments / instalments made against a loan.
        Schema::create('personal_loan_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained('personal_loans')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('principal_part', 18, 2)->default(0);
            $table->decimal('interest_part', 18, 2)->default(0);
            $table->date('paid_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_loan_payments');
    }
};
