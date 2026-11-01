<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Shift;
use Illuminate\Database\Seeder;

/**
 * Branches, departments and standard shifts from the legacy process docs.
 */
class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([['Trece Martires', 'TRECE'], ['Tanza', 'TANZA']] as [$name, $code]) {
            Branch::updateOrCreate(['code' => $code], ['name' => $name]);
        }

        $departments = [
            ['Operations', 'OPS'],
            ['Sales and Marketing', 'SALES'],
            ['Human Resources', 'HR'],
            ['Finance', 'FIN'],
            ['Information Technology', 'IT'],
            ['Warehouse', 'WH'],
        ];

        foreach ($departments as [$name, $code]) {
            Department::updateOrCreate(['code' => $code], ['name' => $name]);
        }

        $mondayToSaturday = [1, 2, 3, 4, 5, 6];

        Shift::updateOrCreate(['name' => 'Rank & File (7:00–4:00)'], [
            'start_time' => '07:00:00',
            'end_time' => '16:00:00',
            'break_minutes' => 60,
            'work_days' => $mondayToSaturday,
        ]);

        Shift::updateOrCreate(['name' => 'Supervisor (8:00–6:00)'], [
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'work_days' => $mondayToSaturday,
        ]);
    }
}
