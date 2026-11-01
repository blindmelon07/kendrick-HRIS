<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EmployeeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee->load(['department', 'branch', 'shift']);

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'employee' => EmployeeResource::make($employee),
                // Versioned so the app refetches after HR uploads a new photo.
                'photo_url' => $employee->photo_path
                    ? route('api.v1.me.photo', ['v' => substr(md5($employee->photo_path), 0, 8)])
                    : null,
                'can' => [
                    'endorse_filings' => $user->can('endorse-filings'),
                ],
            ],
        ]);
    }
}
