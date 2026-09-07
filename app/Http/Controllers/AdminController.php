<?php

namespace App\Http\Controllers;

use App\Models\ReadingChallenge;
use App\Models\ReadingHighlight;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'highlights' => ReadingHighlight::query()->count(),
                'progressEntries' => ReadingProgress::query()->count(),
                'challenges' => ReadingChallenge::query()->count(),
            ],
            'users' => User::query()->latest()->get(),
            'highlights' => ReadingHighlight::query()
                ->with('user:id,name,email')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function destroyHighlight(ReadingHighlight $highlight): RedirectResponse
    {
        $highlight->delete();

        return to_route('admin.dashboard')->with('status', 'Izcēlums dzēsts.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return to_route('admin.dashboard')
                ->with('status', 'Admins nevar izdzēst pats savu kontu.');
        }

        $user->delete();

        return to_route('admin.dashboard')->with('status', 'Lietotājs dzēsts.');
    }
}
