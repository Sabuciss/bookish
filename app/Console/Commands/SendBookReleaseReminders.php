<?php

namespace App\Console\Commands;

use App\Models\BookReleaseReminder;
use App\Notifications\BookReleaseAvailable;
use Illuminate\Console\Command;

class SendBookReleaseReminders extends Command
{
    protected $signature = 'bookish:send-release-reminders';
    protected $description = 'Send notifications for books released today';

    public function handle(): int
    {
        $sentCount = 0;

        BookReleaseReminder::query()
            ->whereNull('notified_at')
            ->whereDate('release_date', '<=', today())
            ->with('user')
            ->chunkById(100, function ($reminders) use (&$sentCount): void {
                foreach ($reminders as $reminder) {
                    $notificationExists = $reminder->user->notifications()
                        ->where('type', BookReleaseAvailable::class)
                        ->where('data->reminder_id', $reminder->id)
                        ->exists();

                    if (! $notificationExists) {
                        $reminder->user->notify(new BookReleaseAvailable($reminder));
                    }

                    $reminder->update([
                        'notified_at' => now(),
                        'read_at' => now(),
                    ]);
                    $sentCount++;
                }
            });

        $this->info('Sent ' . $sentCount . ' release reminder(s).');

        return self::SUCCESS;
    }
}
