<?php

namespace App\Http\Controllers;

use App\Models\BookReleaseReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookReleaseReminderController extends Controller
{
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
            ]
        );

        return response()->json([
            'message' => 'Paziņojums iestatīts.',
            'reminder_id' => $reminder->id,
        ]);
    }

    public function destroy(Request $request, string $volumeId): JsonResponse
    {
        BookReleaseReminder::query()
            ->where('user_id', (int) $request->user()->id)
            ->where('google_volume_id', $volumeId)
            ->delete();

        return response()->json(['message' => 'Paziņojums noņemts.']);
    }
}
