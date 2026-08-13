<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_recurring_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recurring_id')->constrained('personal_recurring_transactions')->cascadeOnDelete();
            $table->string('status'); // success | failed
            $table->foreignId('transaction_id')->nullable()->constrained('personal_transactions')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();

            $table->index(['recurring_id', 'ran_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_recurring_logs');
    }
};
