<?php

namespace Tests\Feature;

use App\Models\ReadingHighlight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
