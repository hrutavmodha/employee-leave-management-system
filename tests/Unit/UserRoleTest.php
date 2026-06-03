<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that legacy lowercase values in the DB are normalized on retrieval.
     */
    public function test_lowercase_role_is_retrieved_as_titlecase(): void
    {
        // Arrange
        // Insert raw database values to bypass Eloquent mutator on write
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee', // lowercase in DB
        ]);

        // Act
        $user = User::find($userId);

        // Assert
        $this->assertEquals('Employee', $user->role);
        $this->assertTrue($user->isEmployee());
        $this->assertFalse($user->isManager());
    }

    /**
     * Test that database default role is 'Employee' and passes authorization check.
     */
    public function test_default_role_in_database_is_employee(): void
    {
        // Arrange
        // Insert raw without specifying 'role' to trigger DB default
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
        ]);

        // Act
        $user = User::find($userId);

        // Assert
        $this->assertEquals('Employee', $user->role);
        $this->assertTrue($user->isEmployee());
    }

    /**
     * Test setting a lowercase role value gets normalized on write/read.
     */
    public function test_setting_lowercase_role_normalizes_successfully(): void
    {
        // Arrange
        $user = new User([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager', // lowercase setter
        ]);

        // Act
        $user->save();

        // Assert
        $this->assertEquals('Manager', $user->role);
        $this->assertTrue($user->isManager());
        
        // Assert raw value stored in DB is also normalized
        $rawRole = DB::table('users')->where('id', $user->id)->value('role');
        $this->assertEquals('Manager', $rawRole);
    }
}
