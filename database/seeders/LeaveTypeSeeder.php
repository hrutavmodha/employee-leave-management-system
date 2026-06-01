<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Annual Leave',
                'allowed_days' => 20,
                'carry_forward' => true,
                'description' => 'Standard paid time off for vacations.',
            ],
            [
                'name' => 'Sick Leave',
                'allowed_days' => 12,
                'carry_forward' => false,
                'description' => 'Leave for medical reasons or recovery.',
            ],
            [
                'name' => 'Casual Leave',
                'allowed_days' => 8,
                'carry_forward' => false,
                'description' => 'Unplanned leave for personal matters.',
            ],
            [
                'name' => 'Maternity Leave',
                'allowed_days' => 90,
                'carry_forward' => false,
                'description' => 'Extended leave for new mothers.',
            ],
            [
                'name' => 'Paternity Leave',
                'allowed_days' => 10,
                'carry_forward' => false,
                'description' => 'Short leave for new fathers.',
            ],
            [
                'name' => 'Work From Home',
                'allowed_days' => 30,
                'carry_forward' => false,
                'description' => 'Allowance for remote work days.',
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
