<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_loans', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_loans', 'penalty_rate')) {
                $table->decimal('penalty_rate', 8, 4)->default(0)->after('interest_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_loans', function (Blueprint $table): void {
            if (Schema::hasColumn('personal_loans', 'penalty_rate')) {
                $table->dropColumn('penalty_rate');
            }
        });
    }
};
