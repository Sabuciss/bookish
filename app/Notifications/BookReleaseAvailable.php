<?php

namespace App\Notifications;

use App\Models\BookReleaseReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookReleaseAvailable extends Notification
{
    use Queueable;

    public function __construct(public BookReleaseReminder $reminder)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'book_release_available',
            'reminder_id' => $this->reminder->id,
            'google_volume_id' => $this->reminder->google_volume_id,
            'title' => $this->reminder->title,
            'author' => $this->reminder->author,
            'release_date' => $this->reminder->release_date->toDateString(),
            'cover_url' => $this->reminder->cover_url,
            'url' => $this->reminder->info_link ?: route('books.show', $this->reminder->google_volume_id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Grāmata ir iznākusi: ' . $this->reminder->title)
            ->greeting('Labas ziņas, ' . $notifiable->name . '!')
            ->line('Grāmata, kurai iestatīji atgādinājumu, ir iznākusi:')
            ->line($this->reminder->title . ' — ' . ($this->reminder->author ?: 'Autors nav norādīts'))
            ->action('Apskatīt Google Books', $this->reminder->info_link ?: config('app.url'));
    }
}
