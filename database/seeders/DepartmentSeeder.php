<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'description' => 'IT support and software development.'],
            ['name' => 'Human Resources', 'description' => 'Recruitment and employee relations.'],
            ['name' => 'Finance', 'description' => 'Accounting and payroll.'],
            ['name' => 'Sales & Marketing', 'description' => 'Client acquisition and branding.'],
            ['name' => 'Operations', 'description' => 'General business operations.'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
