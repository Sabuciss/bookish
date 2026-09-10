<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookListingRequest;
use App\Models\BookListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookListingController extends Controller
{
    public function index(): View
    {
        return view('book-listings.index', [
            'listings' => BookListing::query()
                ->with('user:id,name')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('book-listings.create');
    }

    public function store(StoreBookListingRequest $request): RedirectResponse
    {
        BookListing::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return to_route('book-listings.index')
            ->with('status', 'Sludinājums veiksmīgi publicēts.');
    }
}
