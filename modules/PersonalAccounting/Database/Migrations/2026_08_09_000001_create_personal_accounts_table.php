<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('cash'); // cash | bank | mobile_banking
            $table->string('currency', 10)->default('BDT');
            $table->decimal('balance', 18, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('icon')->nullable();
            $table->string('color')->default('#6366f1');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_accounts');
    }
};
