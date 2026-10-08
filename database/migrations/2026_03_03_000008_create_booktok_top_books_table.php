<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('booktok_top_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('rank_position')->unique();
            $table->string('title');
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('published_year')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booktok_top_books');
        Schema::dropIfExists('authors');
    }
};
