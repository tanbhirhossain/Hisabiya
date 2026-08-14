<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'period_start')) {
                $table->timestamp('period_start')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('payments', 'period_end')) {
                $table->timestamp('period_end')->nullable()->after('period_start');
            }
            if (! Schema::hasColumn('payments', 'is_renewal')) {
                $table->boolean('is_renewal')->default(false)->after('period_end');
            }
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenant_subscriptions', 'grace_ends_at')) {
                $table->timestamp('grace_ends_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('tenant_subscriptions', 'last_renewed_at')) {
                $table->timestamp('last_renewed_at')->nullable()->after('grace_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            foreach (['period_start', 'period_end', 'is_renewal'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table): void {
            foreach (['grace_ends_at', 'last_renewed_at'] as $col) {
                if (Schema::hasColumn('tenant_subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
