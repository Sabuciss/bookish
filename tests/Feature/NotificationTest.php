<?php

namespace Tests\Feature;

use App\Models\BookReleaseReminder;
use App\Models\User;
use App\Notifications\BookReleaseAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_reminder_notification_is_unread_until_the_user_opens_it(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $reminder = BookReleaseReminder::query()->create([
            'user_id' => $user->id,
            'google_volume_id' => 'book-1',
            'title' => 'Jauna grāmata',
            'release_date' => today(),
        ]);

        Artisan::call('bookish:send-release-reminders');

        $notification = $user->fresh()->notifications()->firstOrFail();

        $this->assertSame(BookReleaseAvailable::class, $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertNotNull($reminder->fresh()->notified_at);
        $this->assertNull($reminder->fresh()->read_at);
    }

    public function test_opening_release_notification_marks_its_reminder_as_read(): void
    {
        $user = User::factory()->create();
        $reminder = BookReleaseReminder::query()->create([
            'user_id' => $user->id,
            'google_volume_id' => 'book-2',
            'title' => 'Vēl viena grāmata',
            'release_date' => today(),
        ]);

        $user->notify(new BookReleaseAvailable($reminder));
        $notification = $user->fresh()->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('books.show', $reminder->google_volume_id, absolute: true));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNotNull($reminder->fresh()->read_at);
    }
}
