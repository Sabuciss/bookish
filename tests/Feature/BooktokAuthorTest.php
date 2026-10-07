<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\BooktokFavoriteAuthor;
use App\Models\BooktokTopBook;
use App\Models\User;
use Database\Seeders\BooktokTopBookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BooktokAuthorTest extends TestCase
{
    use RefreshDatabase;

    public function test_booktok_books_share_normalized_author_records(): void
    {
        $this->seed(BooktokTopBookSeeder::class);

        $author = Author::query()->where('name', 'Sarah J. Maas')->firstOrFail();
        $books = BooktokTopBook::query()->whereBelongsTo($author, 'author')->get();

        $this->assertGreaterThan(1, $books->count());
        $this->assertSame($books->count(), $author->booktokTopBooks()->count());
        $this->assertFalse(Schema::hasColumn('booktok_top_books', 'author'));
    }

    public function test_a_user_can_favorite_and_remove_a_normalized_author(): void
    {
        $this->seed(BooktokTopBookSeeder::class);

        $user = User::factory()->create();
        $author = Author::query()->where('name', 'Sarah J. Maas')->firstOrFail();

        $this->actingAs($user)
            ->post(route('booktok.favorite-authors.store'), ['author_id' => $author->id])
            ->assertSessionHasNoErrors();

        $favorite = BooktokFavoriteAuthor::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($author)
            ->firstOrFail();

        $this->assertSame($author->id, $favorite->author->id);
        $this->assertFalse(Schema::hasColumn('booktok_favorite_authors', 'author'));

        $this->actingAs($user)
            ->delete(route('booktok.favorite-authors.destroy', ['author' => $author->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('booktok_favorite_authors', [
            'user_id' => $user->id,
            'author_id' => $author->id,
        ]);
    }

    public function test_nonexistent_author_cannot_be_favorited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('booktok.index'))
            ->post(route('booktok.favorite-authors.store'), ['author_id' => 999999])
            ->assertSessionHasErrors('author_id');

        $this->assertDatabaseCount('booktok_favorite_authors', 0);
    }
}
