<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_listing_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_listing_id')->constrained('book_listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['book_listing_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_listing_applications');
    }
};
