<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_listings', function (Blueprint $table) {
            $table->string('listing_type', 20)->default('sale')->after('user_id');
            $table->string('exchange_book_title')->nullable()->after('book_title');
            $table->decimal('price', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('book_listings', function (Blueprint $table) {
            $table->dropColumn(['listing_type', 'exchange_book_title']);
            $table->decimal('price', 8, 2)->nullable(false)->change();
        });
    }
};
