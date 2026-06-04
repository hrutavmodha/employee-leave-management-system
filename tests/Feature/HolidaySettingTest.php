<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\PublicHoliday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidaySettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake([
            'https://date.nager.at/api/v3/AvailableCountries' =>
                \Illuminate\Support\Facades\Http::response([
                    ['key' => 'IN', 'value' => 'India'],
                    ['key' => 'US', 'value' => 'United States'],
                ], 200),
            'https://date.nager.at/api/v3/PublicHolidays/*' =>
                \Illuminate\Support\Facades\Http::response([
                    [
                        'date' => '2026-08-15',
                        'name' => 'Independence Day',
                    ],
                    [
                        'date' => '2026-10-02',
                        'name' => 'Gandhi Jayanti',
                    ]
                ], 200)
        ]);
    }

    public function test_non_admin_cannot_access_holiday_settings(): void
    {
        $employee = User::factory()->create(['role' => 'Employee']);

        $response = $this->actingAs($employee)->get(route('settings.holidays'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_holiday_settings(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);

        $response = $this->actingAs($admin)->get(route('settings.holidays'));

        $response->assertStatus(200);
        $response->assertViewIs('settings.holidays');
    }

    public function test_admin_can_update_week_holidays(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);

        $response = $this->actingAs($admin)->post(route('settings.week_holidays.update'), [
            'week_holidays' => [5, 6], // Friday & Saturday
        ]);

        $response->assertRedirect(route('settings.holidays'));
        $response->assertSessionHas('success', 'Weekend settings updated successfully.');

        $this->assertEquals([5, 6], Setting::getVal('week_holidays'));
    }

    public function test_admin_can_add_and_delete_company_holiday(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);

        // Add
        $response = $this->actingAs($admin)->post(route('settings.holidays.store'), [
            'name' => 'New Year Day',
            'date' => '2026-01-01',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.holidays'));
        $response->assertSessionHas('success', 'Company holiday added successfully.');

        $this->assertDatabaseHas('public_holidays', [
            'name' => 'New Year Day',
            'date' => '2026-01-01 00:00:00',
        ]);

        $holiday = PublicHoliday::where('name', 'New Year Day')->firstOrFail();

        // Delete
        $responseDelete = $this->actingAs($admin)->delete(route('settings.holidays.destroy', $holiday));

        $responseDelete->assertRedirect(route('settings.holidays'));
        $responseDelete->assertSessionHas('success', 'Company holiday removed successfully.');

        $this->assertDatabaseMissing('public_holidays', [
            'id' => $holiday->id,
        ]);
    }

    public function test_admin_can_auto_import_holidays(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);

        $response = $this->actingAs($admin)->post(route('settings.holidays.import'), [
            'country' => 'IN',
            'year' => 2026,
        ]);

        $response->assertRedirect(route('settings.holidays'));
        $response->assertSessionHas(
            'success',
            'Successfully imported 2 new holidays for IN (2026)!'
        );

        $this->assertDatabaseHas('public_holidays', [
            'name' => 'Independence Day',
            'date' => '2026-08-15 00:00:00',
        ]);

        $this->assertDatabaseHas('public_holidays', [
            'name' => 'Gandhi Jayanti',
            'date' => '2026-10-02 00:00:00',
        ]);
    }
}
