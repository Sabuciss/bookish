<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('book_title');
            $table->string('google_volume_id', 120)->nullable();
            $table->string('book_cover_url', 2048)->nullable();
            $table->unsignedInteger('pages_read');
            $table->unsignedInteger('total_pages')->nullable();
            $table->string('reading_status', 20)->default('in_progress');
            $table->string('emotion', 100);
            $table->date('reading_date');
            $table->time('start_time');
            $table->unsignedInteger('duration_minutes');
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progresses');
    }
};
