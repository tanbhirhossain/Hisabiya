<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('personal_categories')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('period')->default('monthly'); // daily | weekly | monthly | yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_budgets');
    }
};
