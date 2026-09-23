<?php

namespace App\Notifications;

use App\Models\BookListingApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookListingApplicationReceived extends Notification
{
    use Queueable;

    public function __construct(public BookListingApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing(['user', 'listing']);

        return [
            'type' => 'book_listing_application',
            'title' => $this->application->listing->book_title,
            'applicant_name' => $this->application->user->name,
            'listing_id' => $this->application->listing->id,
        ];
    }
}
