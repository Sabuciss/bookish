<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('challenge_type', ['pages', 'time']);
            $table->unsignedInteger('target_value');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('notes', 500)->nullable();
            $table->boolean('is_completed')->default(false);
            $table->string('completion_comment', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_challenges');
    }
};
