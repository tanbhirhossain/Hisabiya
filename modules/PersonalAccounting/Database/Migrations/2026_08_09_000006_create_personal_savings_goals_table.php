<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_savings_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('target_amount', 18, 2);
            $table->decimal('current_amount', 18, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->default('#10b981');
            $table->string('status')->default('active'); // active | completed | paused
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_savings_goals');
    }
};
