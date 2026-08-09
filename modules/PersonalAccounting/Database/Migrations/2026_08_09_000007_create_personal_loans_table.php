<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // personal_loans models both money you borrowed (a liability) and money
        // you lent out (an asset), with interest, amortisation and status tracking.
        Schema::create('personal_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('direction'); // borrowed | lent
            $table->string('counterparty')->nullable(); // bank, institution, or person
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_rate', 8, 4)->default(0); // annual percentage
            $table->string('interest_type')->default('simple'); // simple | compound | flat
            $table->decimal('remaining_balance', 18, 2);
            $table->decimal('total_paid', 18, 2)->default(0);
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->date('next_payment_date')->nullable();
            $table->string('payment_frequency')->default('monthly'); // weekly | biweekly | monthly | quarterly | yearly
            $table->decimal('payment_amount', 18, 2)->default(0);
            $table->string('status')->default('active'); // active | completed | overdue | closed
            $table->string('currency', 10)->default('BDT');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['next_payment_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_loans');
    }
};
