<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscription_plans', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('features');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('subscription_plans', 'feature_flags')) {
                $table->dropColumn('feature_flags');
            }
        });
    }
};
