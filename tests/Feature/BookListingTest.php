<?php

namespace Tests\Feature;

use App\Models\BookListing;
use App\Models\BookListingApplication;
use App\Models\User;
use App\Notifications\BookListingApplicationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookListingTest extends TestCase
{
	use RefreshDatabase;

	public function test_accepted_application_keeps_a_snapshot_and_locks_listing_terms_until_completion(): void
	{
		$seller = User::factory()->create();
		$buyer = User::factory()->create();
		$listing = $this->createSaleListing($seller);

		$this->actingAs($buyer)
			->post(route('book-listings.apply', $listing), ['message' => 'Varu saņemt klātienē.'])
			->assertSessionHasNoErrors();

		$application = BookListingApplication::query()->where('book_listing_id', $listing->id)->firstOrFail();
		$this->assertSame('pending', $application->status);
		$this->assertSame('Sākotnējais nosaukums', $application->listing_snapshot[0]['snapshot']['listing']['book_title']);

		$this->actingAs($buyer)
			->patch(route('book-listings.applications.details.update', [$listing, $application]), [
				'message' => 'Atjaunota ziņa.',
			])
			->assertSessionHasNoErrors();

		$this->actingAs($seller)
			->patch(route('book-listings.applications.update', [$listing, $application]), ['status' => 'accepted'])
			->assertSessionHasNoErrors();

		$acceptedApplication = $application->fresh();
		$this->assertSame('accepted', $acceptedApplication->status);
		$this->assertSame('Atjaunota ziņa.', $acceptedApplication->message);
		$this->assertSame('accepted', $acceptedApplication->listing_snapshot[1]['stage']);

		$this->from(route('book-listings.edit', $listing))
			->actingAs($seller)
			->patch(route('book-listings.update', $listing), [
				'listing_type' => 'sale',
				'book_title' => 'Mainīts nosaukums',
				'author' => 'Autors',
				'condition' => 'Labs stāvoklis',
				'language' => 'Latviešu',
				'price' => 15,
			])
			->assertSessionHas('status');

		$this->assertSame('Sākotnējais nosaukums', $listing->fresh()->book_title);

		$this->actingAs($buyer)
			->patch(route('book-listings.applications.complete', [$listing, $application]))
			->assertSessionHas('status');

		$this->assertSame('completed', $application->fresh()->status);
		$this->assertSame('unavailable', $listing->fresh()->availability);
	}

	public function test_cancelling_an_application_preserves_history_and_allows_reapplication(): void
	{
		$seller = User::factory()->create();
		$buyer = User::factory()->create();
		$listing = $this->createSaleListing($seller);

		$this->actingAs($buyer)
			->post(route('book-listings.apply', $listing), ['message' => 'Pirmais pieteikums.'])
			->assertSessionHasNoErrors();

		$application = BookListingApplication::query()->where('book_listing_id', $listing->id)->firstOrFail();

		$this->delete(route('book-listings.applications.destroy', [$listing, $application]))
			->assertSessionHas('status');

		$this->assertSame('cancelled', $application->fresh()->status);
		$this->assertDatabaseCount('book_listing_applications', 1);

		$this->post(route('book-listings.apply', $listing), ['message' => 'Atkārtots pieteikums.'])
			->assertSessionHasNoErrors();

		$application->refresh();
		$this->assertSame('pending', $application->status);
		$this->assertSame('Atkārtots pieteikums.', $application->message);
		$this->assertSame(['submitted', 'reapplied'], array_column($application->listing_snapshot, 'stage'));
		$this->assertGreaterThanOrEqual(3, count($application->status_history));
	}

	public function test_deleting_applicant_reopens_listing_and_removes_stale_notifications(): void
	{
		$seller = User::factory()->create();
		$buyer = User::factory()->create();
		$listing = $this->createSaleListing($seller);
		$application = BookListingApplication::query()->create([
			'book_listing_id' => $listing->id,
			'user_id' => $buyer->id,
			'message' => 'Pieteikums',
			'status' => 'accepted',
		]);
		$listing->update(['availability' => 'unavailable']);
		$seller->notify(new BookListingApplicationReceived($application));

		$buyer->delete();

		$this->assertDatabaseMissing('book_listing_applications', ['id' => $application->id]);
		$this->assertDatabaseHas('book_listings', [
			'id' => $listing->id,
			'availability' => 'available',
		]);
		$this->assertSame(0, $seller->fresh()->notifications()->count());
	}

	private function createSaleListing(User $seller): BookListing
	{
		return BookListing::query()->create([
			'user_id' => $seller->id,
			'listing_type' => 'sale',
			'book_title' => 'Sākotnējais nosaukums',
			'author' => 'Autors',
			'condition' => 'Labs stāvoklis',
			'language' => 'Latviešu',
			'price' => 10,
			'availability' => 'available',
		]);
	}
}
