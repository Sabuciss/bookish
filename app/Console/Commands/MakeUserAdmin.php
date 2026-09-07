<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'bookish:make-admin {email : The email address of the user}';

    protected $description = 'Grant the admin role to an existing user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $user->update(['role' => 'admin']);
        $this->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
