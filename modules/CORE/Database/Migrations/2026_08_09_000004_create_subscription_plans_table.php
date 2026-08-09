<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription plans offered for each sellable module (e.g. Personal Accounting).
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('module');                       // e.g. personal_accounting
            $table->string('name');                         // e.g. Personal Accounting Pro
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_yearly', 12, 2)->default(0);
            $table->json('permissions')->nullable();        // permission names granted by this plan
            $table->json('features')->nullable();           // feature bullets for the sales UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
