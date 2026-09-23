<?php

namespace App\Notifications;

use App\Models\ReadingChallengeSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingTimerSessionSaved extends Notification
{
    use Queueable;

    public function __construct(public ReadingChallengeSession $session)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reading_timer_session_saved',
            'session_id' => $this->session->id,
            'pages_read' => $this->session->pages_read,
            'elapsed_minutes' => $this->session->elapsed_seconds
                ? (int) floor($this->session->elapsed_seconds / 60)
                : (int) $this->session->planned_minutes,
        ];
    }
}
