<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\BookListing;
use App\Notifications\BookReleaseAvailable;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $readAt = now();
            $reminderIds = $user->unreadNotifications()
                ->where('type', BookReleaseAvailable::class)
                ->get(['data'])
                ->map(fn ($notification) => $notification->data['reminder_id'] ?? null)
                ->filter()
                ->unique()
                ->values();

            if ($reminderIds->isNotEmpty()) {
                $user->bookReleaseReminders()
                    ->whereIn('id', $reminderIds)
                    ->whereNull('read_at')
                    ->update(['read_at' => $readAt]);
            }

            $user->unreadNotifications()->update(['read_at' => $readAt]);
        });

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