<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_release_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('google_volume_id');
            $table->string('title');
            $table->string('author')->nullable();
            $table->date('release_date');
            $table->string('info_link')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'google_volume_id']);
            $table->index(['release_date', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_release_reminders');
    }
};
