<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Services\Timekeeping\TimeLogImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeLogImportController extends Controller
{
    public function store(Request $request, TimeLogImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $result = $importer->import($request->file('file'));

        return Inertia::flash([
            'success' => "Imported {$result['imported']} punches ({$result['duplicates']} already on file).",
            'importResult' => $result,
        ])->back();
    }
}
