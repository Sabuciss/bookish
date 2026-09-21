<?php

namespace App\Console\Commands;

use App\Models\BooktokTopBook;
use App\Services\BookMetadataService;
use Illuminate\Console\Command;

class FetchBooktokGoogleData extends Command
{
    public function __construct(private readonly BookMetadataService $bookMetadata)
    {
        parent::__construct();
    }

    protected $signature = 'booktok:fetch-google-data';
    protected $description = 'Fetch and cache Google Books data for all BookTok top books';

    public function handle(): int
    {
        $books = BooktokTopBook::all();
        $count = 0;

        $this->info('Fetching Google Books data for ' . $books->count() . ' books...');
        $this->withProgressBar($books, function (BooktokTopBook $book) use (&$count) {
            $metadata = $this->bookMetadata->fetchGoogleBookData($book->title, $book->author);

            if (!empty($metadata)) {
                $book->update([
                    'google_thumbnail' => $metadata['thumbnail'] ?? null,
                    'google_volume_id' => $metadata['volume_id'] ?? null,
                    'google_page_count' => $metadata['page_count'] ?? null,
                    'google_published_date' => $metadata['published_date'] ?? null,
                    'google_publisher' => $metadata['publisher'] ?? null,
                    'google_categories' => $metadata['categories'] ?? null,
                    'google_average_rating' => $metadata['average_rating'] ?? null,
                    'google_ratings_count' => $metadata['ratings_count'] ?? null,
                    'google_description' => $metadata['description'] ?? null,
                    'google_preview_link' => $metadata['preview_link'] ?? null,
                    'google_info_link' => $metadata['info_link'] ?? null,
                    'google_data_fetched_at' => now(),
                ]);
                $count++;
            }
        });

        $this->newLine();
        $this->info('Successfully fetched data for ' . $count . ' books!');

        return Command::SUCCESS;
    }

}
