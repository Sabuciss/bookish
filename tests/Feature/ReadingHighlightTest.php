<?php

namespace Tests\Feature;

use App\Models\ReadingHighlight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReadingHighlightTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_own_private_and_other_public_highlights_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPrivateHighlight = ReadingHighlight::query()->create([
            'user_id' => $user->id,
            'book_title' => 'Mana gramata',
            'character' => 'Mans varonis',
            'quote_text' => 'Mana privata piezime',
            'is_public' => false,
        ]);

        ReadingHighlight::query()->create([
            'user_id' => $otherUser->id,
            'book_title' => 'Publiska gramata',
            'character' => 'Cits varonis',
            'quote_text' => 'Publisks citats',
            'is_public' => true,
        ]);

        ReadingHighlight::query()->create([
            'user_id' => $otherUser->id,
            'book_title' => 'Privata gramata',
            'character' => 'Slepts varonis',
            'quote_text' => 'Privats cita lietotaja citats',
            'is_public' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-highlights.index'));

        $response->assertOk();
        $response->assertSeeText($ownPrivateHighlight->quote_text);
        $response->assertSeeText('Publisks citats');
        $response->assertDontSeeText('Privats cita lietotaja citats');
    }

    public function test_user_can_store_highlight_with_defaults_for_optional_fields(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-highlights.store'), [
                'quote_text' => 'Jauns citats',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reading-highlights.index'));

        $this->assertDatabaseHas('reading_highlights', [
            'user_id' => $user->id,
            'quote_text' => 'Jauns citats',
            'is_public' => false,
        ]);
    }

    public function test_user_can_store_public_highlight(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-highlights.store'), [
                'quote_text' => 'Publisks jaunais citats',
                'is_public' => '1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reading-highlights.index'));

        $this->assertDatabaseHas('reading_highlights', [
            'user_id' => $user->id,
            'quote_text' => 'Publisks jaunais citats',
            'is_public' => true,
        ]);
    }

    public function test_quote_text_is_required_when_storing_highlight(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('reading-highlights.create'))
            ->post(route('reading-highlights.store'), []);

        $response
            ->assertSessionHasErrors('quote_text')
            ->assertRedirect(route('reading-highlights.create'));
    }

    public function test_owner_can_edit_and_delete_their_highlight_but_another_user_cannot(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $highlight = ReadingHighlight::query()->create([
            'user_id' => $owner->id,
            'quote_text' => 'Sākotnējais teksts',
            'is_public' => false,
        ]);

        $this->actingAs($otherUser)
            ->patch(route('reading-highlights.update', $highlight), ['quote_text' => 'Nedrīkst mainīt'])
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(route('reading-highlights.update', $highlight), ['quote_text' => 'Atjaunots teksts'])
            ->assertRedirect(route('reading-highlights.index'));

        $this->assertDatabaseHas('reading_highlights', [
            'id' => $highlight->id,
            'quote_text' => 'Atjaunots teksts',
        ]);

        $this->delete(route('reading-highlights.destroy', $highlight))
            ->assertRedirect(route('reading-highlights.index'));

        $this->assertDatabaseMissing('reading_highlights', ['id' => $highlight->id]);
    }

    public function test_highlight_index_is_paginated(): void
    {
        $user = User::factory()->create();
        $this->createHighlights($user, 25);

        $this->actingAs($user)
            ->get(route('reading-highlights.index'))
            ->assertOk()
            ->assertViewHas('highlights', fn ($highlights) => $highlights->count() === 24 && $highlights->hasMorePages());
    }

    public function test_user_cannot_create_more_than_five_hundred_highlights(): void
    {
        $user = User::factory()->create();
        $this->createHighlights($user, 500);

        $this->actingAs($user)
            ->from(route('reading-highlights.create'))
            ->post(route('reading-highlights.store'), ['quote_text' => 'Pārsniedz limitu'])
            ->assertSessionHasErrors('quote_text');

        $this->assertDatabaseCount('reading_highlights', 500);
    }

    private function createHighlights(User $user, int $count): void
    {
        $now = now();

        foreach (array_chunk(range(0, $count - 1), 100) as $indices) {
            DB::table('reading_highlights')->insert(array_map(fn (int $index): array => [
                'user_id' => $user->id,
                'quote_text' => 'Highlight ' . $index,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $indices));
        }
    }
}
