<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_accounts', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_default');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_accounts', function (Blueprint $table): void {
            if (Schema::hasColumn('personal_accounts', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
        });
    }
};
