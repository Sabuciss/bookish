<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('book_listing_applications', 'offered_book_title')) {
            Schema::table('book_listing_applications', function (Blueprint $table) {
                $table->string('offered_book_title')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('book_listing_applications', 'offered_book_title')) {
            Schema::table('book_listing_applications', function (Blueprint $table) {
                $table->dropColumn('offered_book_title');
            });
        }
    }
};
