<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user?->only('id', 'name', 'email', 'role'),
                'employee' => $user?->employee?->only('id', 'employee_number', 'full_name'),
                'can' => [
                    'manageUsers' => $user?->can('manage-users') ?? false,
                    'manageEmployees' => $user?->can('manage-employees') ?? false,
                    'manageTimekeeping' => $user?->can('manage-timekeeping') ?? false,
                    'managePayroll' => $user?->can('manage-payroll') ?? false,
                    'endorseFilings' => $user?->can('endorse-filings') ?? false,
                ],
            ],
        ];
    }
}
