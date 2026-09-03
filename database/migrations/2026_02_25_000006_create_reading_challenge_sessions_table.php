<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_challenge_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->nullable()->constrained('reading_challenges')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('planned_minutes');
            $table->unsignedInteger('elapsed_seconds')->nullable();
            $table->unsignedInteger('pages_read');
            $table->string('notes', 500)->nullable();
            $table->boolean('was_completed')->default(false);
            $table->string('completion_comment', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_challenge_sessions');
    }
};
