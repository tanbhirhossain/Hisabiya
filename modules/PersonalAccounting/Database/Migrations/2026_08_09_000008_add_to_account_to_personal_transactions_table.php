<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_transactions', function (Blueprint $table): void {
            // Destination account for transfers (source is `account_id`).
            $table->foreignId('to_account_id')->nullable()->after('account_id')
                ->constrained('personal_accounts')->nullOnDelete();

            $table->index(['account_id', 'to_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('personal_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('to_account_id');
        });
    }
};
