<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates an administrator account', function () {
    $this->artisan('hris:create-admin', [
        '--name' => 'HR Admin',
        '--email' => 'admin@pkmart.ph',
        '--password' => 'Str0ng!Passw0rd',
    ])->assertSuccessful();

    $user = User::where('email', 'admin@pkmart.ph')->sole();

    expect($user->role)->toBe(UserRole::Admin)
        ->and(Hash::check('Str0ng!Passw0rd', $user->password))->toBeTrue();
});

test('it prompts for missing details', function () {
    $this->artisan('hris:create-admin')
        ->expectsQuestion('Full name', 'HR Admin')
        ->expectsQuestion('Login email', 'admin@pkmart.ph')
        ->expectsQuestion('Password', 'Str0ng!Passw0rd')
        ->assertSuccessful();

    expect(User::where('email', 'admin@pkmart.ph')->exists())->toBeTrue();
});

test('it rejects a duplicate email', function () {
    User::factory()->create(['email' => 'admin@pkmart.ph']);

    $this->artisan('hris:create-admin', [
        '--name' => 'HR Admin',
        '--email' => 'admin@pkmart.ph',
        '--password' => 'Str0ng!Passw0rd',
    ])->assertFailed();

    expect(User::where('email', 'admin@pkmart.ph')->count())->toBe(1);
});
