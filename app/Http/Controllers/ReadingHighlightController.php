<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingHighlightRequest;
use App\Models\ReadingHighlight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
            ->paginate(24)
            ->withQueryString();

        return view('reading-highlights.index', [
            'highlights' => $highlights,
        ]);
    }

    public function create(): View
    {
        return view('reading-highlights.create', ['highlight' => null]);
    }

    public function edit(ReadingHighlight $highlight): View
    {
        abort_unless($highlight->user_id === auth()->id(), 404);

        return view('reading-highlights.create', compact('highlight'));
    }

    public function store(StoreReadingHighlightRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $created = DB::transaction(function () use ($request, $data): bool {
            $user = $request->user();
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();

            if (ReadingHighlight::query()->where('user_id', $user->id)->count() >= 500) {
                return false;
            }

            ReadingHighlight::create([
                ...$data,
                'book_title' => filled($data['book_title'] ?? null) ? $data['book_title'] : 'Nav norādīta grāmata',
                'character' => filled($data['character'] ?? null) ? $data['character'] : 'Nav zināms',
                'is_public' => (bool) ($data['is_public'] ?? false),
                'user_id' => $user->id,
            ]);

            return true;
        });

        if (! $created) {
            return back()->withInput()->withErrors([
                'quote_text' => 'Maksimālais highlight ierakstu skaits vienam lietotājam ir 500.',
            ]);
        }

        return redirect()->route('reading-highlights.index')
            ->with('status', 'Highlight veiksmīgi saglabāts.');
    }

    public function update(StoreReadingHighlightRequest $request, ReadingHighlight $highlight): RedirectResponse
    {
        abort_unless($highlight->user_id === $request->user()->id, 404);
        $highlight->update($request->validated());

        return to_route('reading-highlights.index')->with('status', 'Highlight atjaunināts.');
    }

    public function destroy(ReadingHighlight $highlight): RedirectResponse
    {
        abort_unless($highlight->user_id === auth()->id(), 404);
        $highlight->delete();

        return to_route('reading-highlights.index')->with('status', 'Highlight dzēsts.');
    }
}