<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BooktokTopController;
use App\Http\Controllers\BookReleaseReminderController;
use App\Http\Controllers\GoogleBooksController;
use App\Http\Controllers\ReadingChallengeController;
use App\Http\Controllers\ReadingHighlightController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('reading-shelf.show');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/booktok', [BooktokTopController::class, 'index'])
    ->name('booktok.index');
Route::get('/booktok/{book}', [BooktokTopController::class, 'show'])
    ->name('booktok.show');
Route::get('/api/google-books/top', [GoogleBooksController::class, 'top'])
    ->name('google-books.top');
Route::get('/books/{volumeId}', [GoogleBooksController::class, 'show'])
    ->name('books.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/reading-shelf', [ReadingProgressController::class, 'showBookshelf'])
        ->name('reading-shelf.show');
    Route::get('/reading-progress', [ReadingProgressController::class, 'showProgressTracker'])
        ->name('reading-progress.index');
    Route::post('/reading-progress', [ReadingProgressController::class, 'storeProgressEntry'])
        ->name('reading-progress.store');
    Route::post('/reading-progress/want-to-read', [ReadingProgressController::class, 'storeWantToRead'])
        ->name('reading-progress.want-to-read.store');
    Route::post('/book-release-reminders', [BookReleaseReminderController::class, 'store'])
        ->name('book-release-reminders.store');
    Route::delete('/book-release-reminders/{volumeId}', [BookReleaseReminderController::class, 'destroy'])
        ->name('book-release-reminders.destroy');

    Route::get('/reading-highlights/create', [ReadingHighlightController::class, 'create'])
        ->name('reading-highlights.create');
    Route::get('/reading-highlights', [ReadingHighlightController::class, 'index'])
        ->name('reading-highlights.index');
    Route::post('/reading-highlights', [ReadingHighlightController::class, 'store'])
        ->name('reading-highlights.store');

    Route::get('/reading-challenges', [ReadingChallengeController::class, 'index'])
        ->name('reading-challenges.index');
    Route::post('/reading-challenges', [ReadingChallengeController::class, 'store'])
        ->name('reading-challenges.store');
    Route::get('/reading-challenges/{challenge}/edit', [ReadingChallengeController::class, 'edit'])
        ->name('reading-challenges.edit');
    Route::patch('/reading-challenges/{challenge}', [ReadingChallengeController::class, 'update'])
        ->name('reading-challenges.update');

    Route::get('/reading-timer', [ReadingChallengeController::class, 'timer'])
        ->name('reading-timer.index');
    Route::get('/reading-challenges/results', [ReadingChallengeController::class, 'results'])
        ->name('reading-challenges.results');
    Route::post('/reading-challenges/sessions', [ReadingChallengeController::class, 'storeSession'])
        ->name('reading-challenges.sessions.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::delete('/highlights/{highlight}', [AdminController::class, 'destroyHighlight'])
        ->name('highlights.destroy');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])
        ->name('users.destroy');
});

require __DIR__.'/auth.php';
