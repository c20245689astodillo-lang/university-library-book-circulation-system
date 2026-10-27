<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'title') && ! Schema::hasColumn('books', 'book_title')) {
            Schema::table('books', function (Blueprint $table) {
                $table->renameColumn('title', 'book_title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('books', 'book_title') && ! Schema::hasColumn('books', 'title')) {
            Schema::table('books', function (Blueprint $table) {
                $table->renameColumn('book_title', 'title');
            });
        }
    }
};
