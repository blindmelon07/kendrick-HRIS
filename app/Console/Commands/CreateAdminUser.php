<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first administrator on a fresh server (there is no public registration).
 */
#[Signature('hris:create-admin {--name= : Full name} {--email= : Login email} {--password= : Password (prompted when omitted)}')]
#[Description('Create an HRIS administrator account')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $attributes = [
            'name' => $this->option('name') ?? $this->ask('Full name'),
            'email' => $this->option('email') ?? $this->ask('Login email'),
            'password' => $this->option('password') ?? $this->secret('Password'),
        ];

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        User::create([...$attributes, 'role' => UserRole::Admin]);

        $this->components->info("Administrator {$attributes['email']} created. You can now sign in.");

        return self::SUCCESS;
    }
}
