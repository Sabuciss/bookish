<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingHighlightRequest;
use App\Models\ReadingHighlight;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingHighlightController extends Controller
{
    private const SEEDED_HIGHLIGHTS_EMAIL = 'seeded-highlights@bookish.local';

    public function index(): View
    {
        $userId = (int) auth()->id();

        $highlights = ReadingHighlight::query()
            ->with('user:id,name')
            ->where(function ($query) use ($userId) {
                $query->where('is_public', true)
                    ->orWhere('user_id', $userId)
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('email', self::SEEDED_HIGHLIGHTS_EMAIL);
                    });
            })
            ->latest('id')
            ->get();

        return view('reading-highlights.index', [
            'highlights' => $highlights,
        ]);
    }

    public function create(): View
    {
        return view('reading-highlights.create');
    }

    public function store(StoreReadingHighlightRequest $request): RedirectResponse
    {
        $data = $request->validated();

        ReadingHighlight::create([
            ...$data,
            'book_title' => filled($data['book_title'] ?? null) ? $data['book_title'] : 'Nav norādīta grāmata',
            'character' => filled($data['character'] ?? null) ? $data['character'] : 'Nav zināms',
            'is_public' => (bool) ($data['is_public'] ?? false),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('reading-highlights.index')
            ->with('status', 'Highlight veiksmīgi saglabāts.');
    }
}