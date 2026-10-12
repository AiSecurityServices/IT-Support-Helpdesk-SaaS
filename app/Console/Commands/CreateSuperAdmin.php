<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the initial platform super administrator.
 * Usage: php artisan supportflow:superadmin --name="..." --email="..." --password="..."
 * Password may also come from the SF_SUPERADMIN_PASSWORD env var (never committed).
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'supportflow:superadmin
        {--name= : Full name}
        {--email= : Email address}
        {--password= : Password (min 10 chars; prefer env SF_SUPERADMIN_PASSWORD)}';

    protected $description = 'Create the initial platform super administrator';

    public function handle(): int
    {
        if (User::where('is_super_admin', true)->exists() && ! $this->option('email')) {
            $this->warn('A super administrator already exists. Pass --email to create another.');

            return 1;
        }

        $name = $this->option('name') ?? $this->ask('Full name');
        $email = $this->option('email') ?? $this->ask('Email address');
        $password = $this->option('password') ?? env('SF_SUPERADMIN_PASSWORD') ?? $this->secret('Password (min 10 chars)');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:'.config('supportflow.password_min_length', 10),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => Hash::make($password),
            'role' => 'owner', 'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Super administrator {$user->email} created. Sign in at /login, then visit /admin.");

        return 0;
    }
}
