<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\LeaveCalculationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            LeaveTypeSeeder::class,
        ]);

        $hrDept = \App\Models\Department::where('name', 'Human Resources')->first();

        $admin = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'role' => 'HR/Admin',
                'department_id' => $hrDept ? $hrDept->id : null,
            ]
        );

        // Initialize balances for the admin user so testing works immediately
        app(LeaveCalculationService::class)->initializeBalances($admin);
    }
}
