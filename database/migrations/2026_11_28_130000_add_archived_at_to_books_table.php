<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'students', 'publishers', 'loans', 'fines'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['fines', 'loans', 'publishers', 'students', 'books'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('archived_at');
            });
        }
    }
};
