<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookListingRequest;
use App\Http\Requests\StoreBookListingApplicationRequest;
use App\Models\BookListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookListingController extends Controller
{
    public function index(Request $request): View
    {
        $listingType = $request->routeIs('book-exchange.*') ? 'exchange' : 'sale';

        return view('book-listings.index', [
            'listingType' => $listingType,
            'listings' => BookListing::query()
                ->where('listing_type', $listingType)
                ->with('user:id,name')
                ->with(['applications.user:id,name,email'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        return view('book-listings.create', [
            'listingType' => $request->routeIs('book-exchange.*') ? 'exchange' : 'sale',
        ]);
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

    public function apply(StoreBookListingApplicationRequest $request, BookListing $bookListing): RedirectResponse
    {
        if ($bookListing->user_id === $request->user()->id) {
            return back()->with('status', 'Uz savu sludinājumu pieteikties nevar.');
        }

        if ($bookListing->applications()->where('user_id', $request->user()->id)->exists()) {
            return back()->with('status', 'Tu jau esi pieteicies uz šo grāmatu.');
        }

        $bookListing->applications()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Pieteikums nosūtīts grāmatas pārdevējam.');
    }
}
