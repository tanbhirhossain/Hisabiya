<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('personal_accounts')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('personal_categories')->nullOnDelete();
            $table->string('type'); // income | expense | transfer
            $table->decimal('amount', 18, 2);
            $table->text('note')->nullable();
            $table->date('date');
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('recurring_id')->nullable()->constrained('personal_recurring_transactions')->nullOnDelete();
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'date']);
            $table->index(['account_id', 'date']);
            $table->index(['category_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_transactions');
    }
};
