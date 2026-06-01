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

        $admin = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'role' => 'HR/Admin',
            ]
        );

        // Initialize balances for the admin user so testing works immediately
        app(LeaveCalculationService::class)->initializeBalances($admin);
    }
}
