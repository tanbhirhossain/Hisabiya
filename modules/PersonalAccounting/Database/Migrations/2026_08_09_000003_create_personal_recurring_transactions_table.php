<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_recurring_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('account_id')->constrained('personal_accounts')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('personal_categories')->nullOnDelete();
            $table->string('type'); // income | expense | transfer
            $table->decimal('amount', 18, 2);
            $table->json('template_data')->nullable(); // store extra attributes for the generated transaction
            $table->string('frequency')->default('monthly'); // daily | weekly | monthly | yearly
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'is_active']);
            $table->index('next_run_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_recurring_transactions');
    }
};
