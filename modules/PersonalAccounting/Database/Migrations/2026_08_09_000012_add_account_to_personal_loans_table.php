<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_loans', function (Blueprint $table): void {
            $table->foreignId('account_id')->nullable()->after('contact_id')
                ->constrained('personal_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_loans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
