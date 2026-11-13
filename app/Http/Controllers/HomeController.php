<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
use App\Services\NytBestsellerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, NytBestsellerService $bestsellers): View
    {
        $continueReading = collect();

        if ($request->user()) {
            $continueReading = ReadingProgress::latestSnapshotEntriesForUser((int) $request->user()->id)
                ->filter(fn (ReadingProgress $entry) => $entry->pages_read > 0
                    && $entry->total_pages
                    && $entry->pages_read < $entry->total_pages)
                ->sortByDesc(fn (ReadingProgress $entry) => $entry->reading_date?->timestamp ?? 0)
                ->take(3)
                ->values();
        }

        return view('welcome', [
            'continueReading' => $continueReading,
            'bestsellers' => $bestsellers->weeklyLists(),
        ]);
    }
}
