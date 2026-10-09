<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BooktokTopController;
use App\Http\Controllers\BookReleaseReminderController;
use App\Http\Controllers\GoogleBooksController;
use App\Http\Controllers\ReadingChallengeController;
use App\Http\Controllers\ReadingHighlightController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookListingController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacy-policy', 'legal.privacy')->name('privacy-policy');
Route::view('/terms', 'legal.terms')->name('terms');

Route::get('/dashboard', function () {
    return redirect()->route('reading-shelf.show');
})->middleware('auth')->name('dashboard');

Route::get('/booktok', [BooktokTopController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('booktok.index');
Route::get('/booktok/{book}', [BooktokTopController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('booktok.show');
Route::get('/api/google-books/top', [GoogleBooksController::class, 'top'])
    ->middleware('throttle:120,1')
    ->name('google-books.top');
Route::get('/books/{volumeId}', [GoogleBooksController::class, 'show'])
    ->middleware('throttle:120,1')
    ->name('books.show');
Route::get('/book-listings', [BookListingController::class, 'index'])
        ->middleware('throttle:120,1')
    ->name('book-listings.index');
Route::get('/book-exchange', [BookListingController::class, 'index'])
        ->middleware('throttle:120,1')
    ->name('book-exchange.index');

Route::middleware('auth')->group(function () {
    Route::get('/book-listings/create', [BookListingController::class, 'create'])
        ->name('book-listings.create');
    Route::get('/book-exchange/create', [BookListingController::class, 'create'])
        ->name('book-exchange.create');
    Route::get('/book-listings/{bookListing}/edit', [BookListingController::class, 'edit'])
        ->name('book-listings.edit');
    Route::patch('/book-listings/{bookListing}', [BookListingController::class, 'update'])
        ->name('book-listings.update');
    Route::delete('/book-listings/{bookListing}', [BookListingController::class, 'destroy'])
        ->name('book-listings.destroy');
    Route::post('/book-listings', [BookListingController::class, 'store'])
        ->name('book-listings.store');
    Route::post('/book-listings/{bookListing}/apply', [BookListingController::class, 'apply'])
        ->middleware('throttle:10,1')
        ->name('book-listings.apply');
    Route::delete('/book-listings/{bookListing}/applications/{application}', [BookListingController::class, 'withdrawApplication'])
        ->name('book-listings.applications.destroy');
    Route::patch('/book-listings/{bookListing}/applications/{application}', [BookListingController::class, 'updateApplication'])
        ->name('book-listings.applications.update');
    Route::patch('/book-listings/{bookListing}/applications/{application}/details', [BookListingController::class, 'updateApplicantApplication'])
        ->name('book-listings.applications.details.update');
    Route::patch('/book-listings/{bookListing}/applications/{application}/complete', [BookListingController::class, 'completeApplication'])
        ->name('book-listings.applications.complete');
    Route::post('/book-listings/{bookListing}/applications/{application}/messages', [BookListingController::class, 'sendMessage'])
        ->middleware('throttle:20,1')
        ->name('book-listings.applications.messages.store');
    Route::get('/reading-shelf', [ReadingProgressController::class, 'showBookshelf'])
        ->name('reading-shelf.show');
    Route::get('/reading-progress', [ReadingProgressController::class, 'showProgressTracker'])
        ->name('reading-progress.index');
    Route::post('/reading-progress', [ReadingProgressController::class, 'storeProgressEntry'])
        ->name('reading-progress.store');
    Route::delete('/reading-progress/{entryId}', [ReadingProgressController::class, 'destroy'])
        ->name('reading-progress.destroy');
    Route::post('/reading-progress/want-to-read', [ReadingProgressController::class, 'storeWantToRead'])
        ->name('reading-progress.want-to-read.store');
    Route::post('/book-release-reminders', [BookReleaseReminderController::class, 'store'])
        ->name('book-release-reminders.store');
    Route::get('/book-release-reminders', [BookReleaseReminderController::class, 'index'])
        ->name('book-release-reminders.index');
    Route::post('/booktok/favorite-authors', [BooktokTopController::class, 'addFavoriteAuthor'])
        ->name('booktok.favorite-authors.store');
    Route::delete('/booktok/favorite-authors/{author}', [BooktokTopController::class, 'removeFavoriteAuthor'])
        ->name('booktok.favorite-authors.destroy');
    Route::delete('/book-release-reminders/{volumeId}', [BookReleaseReminderController::class, 'destroy'])
        ->name('book-release-reminders.destroy');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::get('/book-release-reminders/{reminder}/open', [BookReleaseReminderController::class, 'open'])
        ->name('book-release-reminders.open');

    Route::get('/reading-highlights/create', [ReadingHighlightController::class, 'create'])
        ->name('reading-highlights.create');
    Route::get('/reading-highlights', [ReadingHighlightController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('reading-highlights.index');
    Route::post('/reading-highlights', [ReadingHighlightController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('reading-highlights.store');
    Route::get('/reading-highlights/{highlight}/edit', [ReadingHighlightController::class, 'edit'])
        ->name('reading-highlights.edit');
    Route::patch('/reading-highlights/{highlight}', [ReadingHighlightController::class, 'update'])
        ->name('reading-highlights.update');
    Route::delete('/reading-highlights/{highlight}', [ReadingHighlightController::class, 'destroy'])
        ->name('reading-highlights.destroy');

    Route::get('/reading-challenges', [ReadingChallengeController::class, 'index'])
        ->name('reading-challenges.index');
    Route::post('/reading-challenges', [ReadingChallengeController::class, 'store'])
        ->name('reading-challenges.store');
    Route::get('/reading-challenges/{challenge}/edit', [ReadingChallengeController::class, 'edit'])
        ->name('reading-challenges.edit');
    Route::patch('/reading-challenges/{challenge}', [ReadingChallengeController::class, 'update'])
        ->name('reading-challenges.update');
    Route::delete('/reading-challenges/{challenge}', [ReadingChallengeController::class, 'destroy'])
        ->name('reading-challenges.destroy');

    Route::get('/reading-timer', [ReadingChallengeController::class, 'timer'])
        ->name('reading-timer.index');
    Route::get('/reading-challenges/results', [ReadingChallengeController::class, 'results'])
        ->name('reading-challenges.results');
    Route::post('/reading-timer/sessions', [ReadingChallengeController::class, 'startTimer'])
        ->name('reading-timer.sessions.start');
    Route::post('/reading-timer/sessions/{session}/pause', [ReadingChallengeController::class, 'pauseTimer'])
        ->name('reading-timer.sessions.pause');
    Route::post('/reading-timer/sessions/{session}/resume', [ReadingChallengeController::class, 'resumeTimer'])
        ->name('reading-timer.sessions.resume');
    Route::post('/reading-timer/sessions/{session}/complete', [ReadingChallengeController::class, 'completeTimer'])
        ->name('reading-timer.sessions.complete');
    Route::delete('/reading-timer/sessions/{session}', [ReadingChallengeController::class, 'destroyTimer'])
        ->name('reading-timer.sessions.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::delete('/highlights/{highlight}', [AdminController::class, 'destroyHighlight'])
        ->name('highlights.destroy');
    Route::delete('/book-listings/{bookListing}', [AdminController::class, 'destroyBookListing'])
        ->name('book-listings.destroy');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])
        ->name('users.destroy');
});

require __DIR__.'/auth.php';
