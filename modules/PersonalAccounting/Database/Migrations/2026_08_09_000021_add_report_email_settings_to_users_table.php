<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'personal_report_email_enabled')) {
                $table->boolean('personal_report_email_enabled')->default(false)->after('phone');
            }
            if (! Schema::hasColumn('users', 'personal_report_email_day')) {
                $table->unsignedTinyInteger('personal_report_email_day')->default(1)->after('personal_report_email_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['personal_report_email_enabled', 'personal_report_email_day'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
