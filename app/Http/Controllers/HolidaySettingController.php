<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use App\Models\Setting;
use Illuminate\Http\Request;

class HolidaySettingController extends Controller
{
    /**
     * Display the settings dashboard for weekends and company holidays.
     */
    public function index()
    {
        $weekHolidays = Setting::getVal('week_holidays', [0, 6]);
        $publicHolidays = PublicHoliday::orderBy('date')->get();

        $countries = \Illuminate\Support\Facades\Cache::remember(
            'holidays.countries',
            86400,
            function () {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(5)->get(
                        'https://date.nager.at/api/v3/AvailableCountries'
                    );
                    if ($response->successful()) {
                        return $response->json();
                    }
                } catch (\Exception $e) {
                    // Fallback to static list on error
                }

                return [
                    ['key' => 'IN', 'value' => 'India'],
                    ['key' => 'US', 'value' => 'United States'],
                    ['key' => 'GB', 'value' => 'United Kingdom'],
                    ['key' => 'CA', 'value' => 'Canada'],
                    ['key' => 'AU', 'value' => 'Australia'],
                    ['key' => 'DE', 'value' => 'Germany'],
                    ['key' => 'FR', 'value' => 'France'],
                    ['key' => 'JP', 'value' => 'Japan'],
                ];
            }
        );

        return view(
            'settings.holidays',
            compact('weekHolidays', 'publicHolidays', 'countries')
        );
    }

    /**
     * Store a newly created company holiday.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:public_holidays,date',
        ]);

        PublicHoliday::create([
            'name' => $request->name,
            'date' => $request->date,
        ]);

        return redirect()
            ->route('settings.holidays')
            ->with('success', 'Company holiday added successfully.');
    }

    /**
     * Update the week holidays configuration.
     */
    public function updateWeekHolidays(Request $request)
    {
        $request->validate([
            'week_holidays' => 'nullable|array',
            'week_holidays.*' => 'integer|between:0,6',
        ]);

        $weekHolidays = $request->input('week_holidays', []);
        Setting::setVal('week_holidays', $weekHolidays);

        return redirect()
            ->route('settings.holidays')
            ->with('success', 'Weekend settings updated successfully.');
    }

    /**
     * Remove the specified company holiday.
     */
    public function destroy(PublicHoliday $publicHoliday)
    {
        $publicHoliday->delete();

        return redirect()
            ->route('settings.holidays')
            ->with('success', 'Company holiday removed successfully.');
    }

    /**
     * Auto-import holidays from public Nager.Date API.
     */
    public function import(Request $request)
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'year' => 'required|integer|between:2020,2035',
        ]);

        $country = $request->country;
        $year = $request->year;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get(
                "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}"
            );

            if ($response->successful()) {
                $holidays = $response->json();
                $importedCount = 0;

                foreach ($holidays as $item) {
                    $date = $item['date'] ?? null;
                    $name = $item['name'] ?? null;

                    if ($date && $name) {
                        // Check if holiday already exists for this date
                        $exists = PublicHoliday::where('date', $date)->exists();
                        if (!$exists) {
                            PublicHoliday::create([
                                'name' => $name,
                                'date' => $date,
                            ]);
                            $importedCount++;
                        }
                    }
                }

                $msg = "Successfully imported {$importedCount} new holidays " .
                    "for {$country} ({$year})!";
                return redirect()
                    ->route('settings.holidays')
                    ->with('success', $msg);
            }

            return redirect()
                ->route('settings.holidays')
                ->with('error', 'Failed to fetch holidays from the API. Please try again.');
        } catch (\Exception $e) {
            return redirect()
                ->route('settings.holidays')
                ->with('error', 'Connection to API failed: ' . $e->getMessage());
        }
    }
}
