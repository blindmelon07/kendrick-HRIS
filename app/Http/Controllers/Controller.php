<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Read a `from`/`to` date range from the query string, defaulting to the current cut-off.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function cutoffRange(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'before_or_equal:'.CarbonImmutable::parse($request->input('from', 'today'))->addDays(62)->toDateString()],
        ]);

        if ($request->filled(['from', 'to'])) {
            return [CarbonImmutable::parse($request->input('from')), CarbonImmutable::parse($request->input('to'))];
        }

        return PayrollPeriod::rangeContaining(CarbonImmutable::today());
    }
}
