<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_loan_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_loan_payments', 'penalty_amount')) {
                $table->decimal('penalty_amount', 18, 2)->default(0)->after('interest_part');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_loan_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('personal_loan_payments', 'penalty_amount')) {
                $table->dropColumn('penalty_amount');
            }
        });
    }
};
