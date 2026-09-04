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
        return ['mail'];
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
