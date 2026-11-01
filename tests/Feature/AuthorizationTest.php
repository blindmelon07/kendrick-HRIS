<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('each role only reaches its own modules', function (UserRole $role, string $route, int $status) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)->get(route($route))->assertStatus($status);
})->with([
    'employee → employees' => [UserRole::Employee, 'employees.index', 403],
    'employee → time logs' => [UserRole::Employee, 'timekeeping.time-logs.index', 403],
    'employee → payroll' => [UserRole::Employee, 'payroll.periods.index', 403],
    'employee → users' => [UserRole::Employee, 'users.index', 403],
    'time keeper → time logs' => [UserRole::HrTimekeeper, 'timekeeping.time-logs.index', 200],
    'time keeper → payroll' => [UserRole::HrTimekeeper, 'payroll.periods.index', 403],
    'payroll officer → payroll' => [UserRole::HrPayroll, 'payroll.periods.index', 200],
    'payroll officer → filings approval' => [UserRole::HrPayroll, 'timekeeping.filings.index', 403],
    'finance → payroll' => [UserRole::Finance, 'payroll.periods.index', 200],
    'finance → employees' => [UserRole::Finance, 'employees.index', 403],
    'admin → users' => [UserRole::Admin, 'users.index', 200],
]);

test('shared props expose the user permissions', function () {
    $this->actingAs(User::factory()->role(UserRole::HrPayroll)->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.managePayroll', true)
            ->where('auth.can.manageTimekeeping', false)
            ->where('auth.can.manageEmployees', true)
            ->missing('auth.user.password'));
});

test('only department heads can reach endorsements', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('endorsements.index'))
        ->assertForbidden();
});

test('admins cannot demote or delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => UserRole::Employee->value])
        ->assertRedirect();
    $this->actingAs($admin)->delete(route('users.destroy', $admin));

    expect($admin->fresh())->not->toBeNull()
        ->and($admin->fresh()->role)->toBe(UserRole::Admin);
});
