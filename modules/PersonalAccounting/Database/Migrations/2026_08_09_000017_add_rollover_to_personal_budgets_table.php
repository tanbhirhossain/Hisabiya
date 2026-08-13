<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_budgets', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_budgets', 'rollover_enabled')) {
                $table->boolean('rollover_enabled')->default(false)->after('end_date');
            }
            if (! Schema::hasColumn('personal_budgets', 'rollover_amount')) {
                $table->decimal('rollover_amount', 18, 2)->default(0)->after('rollover_enabled');
            }
            if (! Schema::hasColumn('personal_budgets', 'notify_at_percent')) {
                $table->unsignedInteger('notify_at_percent')->default(80)->after('rollover_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_budgets', function (Blueprint $table): void {
            foreach (['rollover_enabled', 'rollover_amount', 'notify_at_percent'] as $col) {
                if (Schema::hasColumn('personal_budgets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
