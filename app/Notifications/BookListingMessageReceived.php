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
        $listing = $this->message->application->listing;

        return [
            'type' => 'book_listing_message_received',
            'listing_id' => $listing->id,
            'application_id' => $this->message->application->id,
            'title' => $listing->book_title,
            'url' => $listing->notificationUrl('book-message-' . $this->message->id),
        ];
    }
}
