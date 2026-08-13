<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_transactions', 'status')) {
                $table->string('status')->default('cleared')->after('is_recurring'); // cleared | pending
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('personal_transactions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
