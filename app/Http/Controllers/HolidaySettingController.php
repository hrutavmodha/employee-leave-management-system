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

        return view('settings.holidays', compact('weekHolidays', 'publicHolidays'));
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
}
