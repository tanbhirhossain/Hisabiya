<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_loans', function (Blueprint $table): void {
            $table->foreignId('contact_id')->nullable()->after('counterparty')
                ->constrained('personal_contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_loans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contact_id');
        });
    }
};
