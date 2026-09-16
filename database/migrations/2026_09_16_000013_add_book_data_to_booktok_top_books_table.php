<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booktok_top_books', function (Blueprint $table) {
            $table->text('google_thumbnail')->nullable();
            $table->string('google_volume_id', 120)->nullable();
            $table->unsignedInteger('google_page_count')->nullable();
            $table->string('google_published_date')->nullable();
            $table->string('google_publisher')->nullable();
            $table->text('google_categories')->nullable();
            $table->decimal('google_average_rating', 3, 2)->nullable();
            $table->unsignedInteger('google_ratings_count')->nullable();
            $table->longText('google_description')->nullable();
            $table->text('google_preview_link')->nullable();
            $table->text('google_info_link')->nullable();
            $table->timestamp('google_data_fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('booktok_top_books', function (Blueprint $table) {
            $table->dropColumn([
                'google_thumbnail',
                'google_volume_id',
                'google_page_count',
                'google_published_date',
                'google_publisher',
                'google_categories',
                'google_average_rating',
                'google_ratings_count',
                'google_description',
                'google_preview_link',
                'google_info_link',
                'google_data_fetched_at',
            ]);
        });
    }
};
