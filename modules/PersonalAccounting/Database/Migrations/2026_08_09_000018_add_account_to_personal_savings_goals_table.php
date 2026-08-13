<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_savings_goals', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_savings_goals', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('user_id')
                    ->constrained('personal_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_savings_goals', function (Blueprint $table): void {
            if (Schema::hasColumn('personal_savings_goals', 'account_id')) {
                $table->dropConstrainedForeignId('account_id');
            }
        });
    }
};
