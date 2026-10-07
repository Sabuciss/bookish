<?php

namespace App\Http\Controllers;

use App\Models\BookReleaseReminder;
use App\Notifications\BookReleaseAvailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookReleaseReminderController extends Controller
{
    public function index(Request $request): View
    {
        $remindersQuery = BookReleaseReminder::query()
            ->where('user_id', (int) $request->user()->id)
            ->whereNull('notified_at')
            ->whereDate('release_date', '>=', today())
            ->orderBy('release_date');

        $calendarEvents = (clone $remindersQuery)
            ->get(['release_date', 'title'])
            ->map(fn (BookReleaseReminder $reminder): array => [
                'date' => $reminder->release_date->toDateString(),
                'title' => $reminder->title,
            ])
            ->all();
        $reminders = $remindersQuery->paginate(20)->withQueryString();

        return view('book-release-reminders.index', [
            'reminders' => $reminders,
            'calendarEvents' => $calendarEvents,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'google_volume_id' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'release_date' => ['required', 'date'],
            'info_link' => ['nullable', 'url', 'max:2048'],
            'cover_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $reminder = BookReleaseReminder::updateOrCreate(
            [
                'user_id' => (int) $request->user()->id,
                'google_volume_id' => $data['google_volume_id'],
            ],
            [
                'title' => $data['title'],
                'author' => $data['author'] ?? null,
                'release_date' => $data['release_date'],
                'info_link' => $data['info_link'] ?? null,
                'cover_url' => $data['cover_url'] ?? null,
                'notified_at' => null,
                'read_at' => null,
            ]
        );

        $request->user()->notifications()
            ->where('type', BookReleaseAvailable::class)
            ->where('data->reminder_id', $reminder->id)
            ->delete();

        return response()->json([
            'message' => 'Paziņojums iestatīts.',
            'reminder_id' => $reminder->id,
        ]);
    }

    public function destroy(Request $request, string $volumeId): JsonResponse|RedirectResponse
    {
        $reminder = BookReleaseReminder::query()
            ->where('user_id', (int) $request->user()->id)
            ->where('google_volume_id', $volumeId)
            ->first();

        if ($reminder) {
            $request->user()->notifications()
                ->where('type', BookReleaseAvailable::class)
                ->where('data->reminder_id', $reminder->id)
                ->delete();
            $reminder->delete();
        }

        if (! $request->expectsJson()) {
            return redirect()->back()->with('status', 'Paziņojums noņemts.');
        }

        return response()->json(['message' => 'Paziņojums noņemts.']);
    }

    public function open(Request $request, int $reminderId): RedirectResponse
    {
        $reminder = BookReleaseReminder::query()
            ->where('user_id', (int) $request->user()->id)
            ->findOrFail($reminderId);
        $reminder->update(['read_at' => now()]);

        return redirect()->to($reminder->info_link ?: route('books.show', $reminder->google_volume_id));
    }
}
