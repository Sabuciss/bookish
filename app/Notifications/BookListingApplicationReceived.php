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
        $listing = $this->application->listing;

        return [
            'type' => 'book_listing_application',
            'title' => $listing->book_title,
            'applicant_name' => $this->application->user->name,
            'application_id' => $this->application->id,
            'listing_id' => $listing->id,
            'url' => $listing->notificationUrl('book-application-' . $this->application->id),
        ];
    }
}
