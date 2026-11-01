<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves employee profile photos from private storage to HR staff and the employee themselves.
 */
class EmployeePhotoController extends Controller
{
    public function show(Request $request, Employee $employee): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->can('manage-employees') || $user->employee?->is($employee), 403);
        abort_unless($employee->photo_path && Storage::disk('local')->exists($employee->photo_path), 404);

        return Storage::disk('local')->response($employee->photo_path, headers: [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
