<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenant_subscriptions', 'billing_status')) {
                $table->string('billing_status')->default('pending')->after('status');
            }
            if (! Schema::hasColumn('tenant_subscriptions', 'checkout_session_id')) {
                $table->string('checkout_session_id')->nullable()->after('billing_status');
            }
            if (! Schema::hasColumn('tenant_subscriptions', 'provider')) {
                $table->string('provider')->nullable()->after('checkout_session_id');
            }
            if (! Schema::hasColumn('tenant_subscriptions', 'provider_ref')) {
                $table->string('provider_ref')->nullable()->after('provider');
            }
            if (! Schema::hasColumn('tenant_subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('provider_ref');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table): void {
            foreach (['billing_status', 'checkout_session_id', 'provider', 'provider_ref', 'trial_ends_at'] as $col) {
                if (Schema::hasColumn('tenant_subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
