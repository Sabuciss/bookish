<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\BookListing;

class NotificationController extends Controller
{
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function open(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        if (isset($notification->data['reminder_id'])) {
            $request->user()->bookReleaseReminders()
                ->whereKey($notification->data['reminder_id'])
                ->update(['read_at' => now()]);
        }

        $url = $notification->data['url'] ?? null;
        if (! $url && isset($notification->data['listing_id'])) {
            $url = BookListing::query()->find($notification->data['listing_id'])?->notificationUrl();
        }
        if (! $url && isset($notification->data['session_id'])) {
            $url = route('reading-challenges.results', [
                'scope' => 'mine',
                'period' => 'all',
                'session_id' => $notification->data['session_id'],
            ]);
        }

        return redirect()->to($url ?: route('dashboard'));
    }
}