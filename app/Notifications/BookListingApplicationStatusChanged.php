<?php

namespace App\Notifications;

use App\Models\BookListingApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookListingApplicationStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public BookListingApplication $application,
        public string $status,
        public string $reason = 'rejected',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing('listing');
        $listing = $this->application->listing;

        return [
            'type' => 'book_listing_application_status_changed',
            'status' => $this->status,
            'reason' => $this->reason,
            'application_id' => $this->application->id,
            'title' => $listing->book_title,
            'listing_id' => $listing->id,
            'url' => $listing->notificationUrl('book-application-' . $this->application->id),
        ];
    }
}
