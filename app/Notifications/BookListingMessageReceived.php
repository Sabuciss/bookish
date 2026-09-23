<?php

namespace App\Notifications;

use App\Models\BookListingMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookListingMessageReceived extends Notification
{
    use Queueable;

    public function __construct(public BookListingMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing('application.listing');

        return [
            'type' => 'book_listing_message_received',
            'listing_id' => $this->message->application->listing->id,
            'title' => $this->message->application->listing->book_title,
        ];
    }
}
