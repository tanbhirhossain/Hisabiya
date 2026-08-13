<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_recurring_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_recurring_transactions', 'end_type')) {
                $table->string('end_type')->default('never')->after('frequency'); // never | on_date | after_occurrences
            }
            if (! Schema::hasColumn('personal_recurring_transactions', 'end_date')) {
                $table->date('end_date')->nullable()->after('end_type');
            }
            if (! Schema::hasColumn('personal_recurring_transactions', 'max_occurrences')) {
                $table->unsignedInteger('max_occurrences')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('personal_recurring_transactions', 'occurrences_count')) {
                $table->unsignedInteger('occurrences_count')->default(0)->after('max_occurrences');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_recurring_transactions', function (Blueprint $table): void {
            foreach (['end_type', 'end_date', 'max_occurrences', 'occurrences_count'] as $col) {
                if (Schema::hasColumn('personal_recurring_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
