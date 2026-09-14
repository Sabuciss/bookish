<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('listing_type', 20)->default('sale');
            $table->string('book_title');
            $table->string('exchange_book_title')->nullable();
            $table->string('author')->nullable();
            $table->string('condition', 30);
            $table->string('language', 50);
            $table->decimal('price', 8, 2)->nullable();
            $table->string('availability', 20)->default('available');
            $table->text('description')->nullable();
            $table->string('contact_email');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_listings');
    }
};
