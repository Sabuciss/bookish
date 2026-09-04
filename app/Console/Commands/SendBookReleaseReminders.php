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
        $reminders = BookReleaseReminder::query()
            ->whereNull('notified_at')
            ->whereDate('release_date', '<=', today())
            ->with('user')
            ->get();

        foreach ($reminders as $reminder) {
            $reminder->user->notify(new BookReleaseAvailable($reminder));
            $reminder->update(['notified_at' => now()]);
        }

        $this->info('Sent ' . $reminders->count() . ' release reminder(s).');

        return self::SUCCESS;
    }
}
