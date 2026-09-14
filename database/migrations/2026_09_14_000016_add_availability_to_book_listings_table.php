<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('book_listings', 'availability')) {
            Schema::table('book_listings', function (Blueprint $table) {
                $table->string('availability', 20)->default('available')->after('listing_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('book_listings', 'availability')) {
            Schema::table('book_listings', function (Blueprint $table) {
                $table->dropColumn('availability');
            });
        }
    }
};