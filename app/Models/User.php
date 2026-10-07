<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BookReleaseReminder;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $acceptedApplications = $user->applications()
                ->where('status', 'accepted')
                ->with('listing')
                ->get();

            foreach ($user->applications()->pluck('id') as $applicationId) {
                DB::table('notifications')
                    ->where('data->application_id', $applicationId)
                    ->delete();
            }

            foreach ($acceptedApplications as $application) {
                $listing = $application->listing;
                if (! $listing) {
                    continue;
                }

                $otherActiveApplicationExists = $listing->applications()
                    ->where('id', '!=', $application->id)
                    ->whereIn('status', ['accepted', 'completed'])
                    ->exists();

                if (! $otherActiveApplicationExists) {
                    $listing->update(['availability' => 'available']);
                }
            }

            foreach ($user->bookListings()->pluck('id') as $listingId) {
                DB::table('notifications')
                    ->where('data->listing_id', $listingId)
                    ->delete();
            }

            $user->notifications()->delete();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function bookReleaseReminders(): HasMany
    {
        return $this->hasMany(BookReleaseReminder::class);
    }

    public function bookListings(): HasMany
    {
        return $this->hasMany(BookListing::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(BookListingApplication::class);
    }

    public function booktokFavoriteAuthors(): HasMany
    {
        return $this->hasMany(BooktokFavoriteAuthor::class);
    }
}
