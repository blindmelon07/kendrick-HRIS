<?php

namespace Database\Seeders;

use App\Enums\HolidayType;
use App\Models\Holiday;
use Illuminate\Database\Seeder;

/**
 * 2026 Philippine holidays. Verify against the official proclamation and
 * add any declared special days through the Holidays page.
 */
class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            ['2026-01-01', 'New Year\'s Day', HolidayType::Regular],
            ['2026-04-02', 'Maundy Thursday', HolidayType::Regular],
            ['2026-04-03', 'Good Friday', HolidayType::Regular],
            ['2026-04-09', 'Araw ng Kagitingan', HolidayType::Regular],
            ['2026-05-01', 'Labor Day', HolidayType::Regular],
            ['2026-06-12', 'Independence Day', HolidayType::Regular],
            ['2026-08-31', 'National Heroes Day', HolidayType::Regular],
            ['2026-11-30', 'Bonifacio Day', HolidayType::Regular],
            ['2026-12-25', 'Christmas Day', HolidayType::Regular],
            ['2026-12-30', 'Rizal Day', HolidayType::Regular],
            ['2026-08-21', 'Ninoy Aquino Day', HolidayType::SpecialNonWorking],
            ['2026-11-01', 'All Saints\' Day', HolidayType::SpecialNonWorking],
            ['2026-12-08', 'Feast of the Immaculate Conception', HolidayType::SpecialNonWorking],
            ['2026-12-24', 'Christmas Eve', HolidayType::SpecialNonWorking],
            ['2026-12-31', 'Last Day of the Year', HolidayType::SpecialNonWorking],
        ];

        foreach ($holidays as [$date, $name, $type]) {
            Holiday::updateOrCreate(['date' => $date], ['name' => $name, 'type' => $type]);
        }
    }
}
