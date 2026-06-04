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
        $years = range(date('Y') - 2, date('Y') + 5);

        $countries = [
            ['countryCode' => 'AF', 'name' => 'Afghanistan'],
            ['countryCode' => 'AL', 'name' => 'Albania'],
            ['countryCode' => 'DZ', 'name' => 'Algeria'],
            ['countryCode' => 'AS', 'name' => 'American Samoa'],
            ['countryCode' => 'AD', 'name' => 'Andorra'],
            ['countryCode' => 'AO', 'name' => 'Angola'],
            ['countryCode' => 'AI', 'name' => 'Anguilla'],
            ['countryCode' => 'AQ', 'name' => 'Antarctica'],
            ['countryCode' => 'AG', 'name' => 'Antigua and Barbuda'],
            ['countryCode' => 'AR', 'name' => 'Argentina'],
            ['countryCode' => 'AM', 'name' => 'Armenia'],
            ['countryCode' => 'AW', 'name' => 'Aruba'],
            ['countryCode' => 'AU', 'name' => 'Australia'],
            ['countryCode' => 'AT', 'name' => 'Austria'],
            ['countryCode' => 'AZ', 'name' => 'Azerbaijan'],
            ['countryCode' => 'BS', 'name' => 'Bahamas'],
            ['countryCode' => 'BH', 'name' => 'Bahrain'],
            ['countryCode' => 'BD', 'name' => 'Bangladesh'],
            ['countryCode' => 'BB', 'name' => 'Barbados'],
            ['countryCode' => 'BY', 'name' => 'Belarus'],
            ['countryCode' => 'BE', 'name' => 'Belgium'],
            ['countryCode' => 'BZ', 'name' => 'Belize'],
            ['countryCode' => 'BJ', 'name' => 'Benin'],
            ['countryCode' => 'BM', 'name' => 'Bermuda'],
            ['countryCode' => 'BT', 'name' => 'Bhutan'],
            ['countryCode' => 'BO', 'name' => 'Bolivia'],
            ['countryCode' => 'BA', 'name' => 'Bosnia and Herzegovina'],
            ['countryCode' => 'BW', 'name' => 'Botswana'],
            ['countryCode' => 'BR', 'name' => 'Brazil'],
            ['countryCode' => 'BN', 'name' => 'Brunei'],
            ['countryCode' => 'BG', 'name' => 'Bulgaria'],
            ['countryCode' => 'BF', 'name' => 'Burkina Faso'],
            ['countryCode' => 'BI', 'name' => 'Burundi'],
            ['countryCode' => 'CV', 'name' => 'Cabo Verde'],
            ['countryCode' => 'KH', 'name' => 'Cambodia'],
            ['countryCode' => 'CM', 'name' => 'Cameroon'],
            ['countryCode' => 'CA', 'name' => 'Canada'],
            ['countryCode' => 'KY', 'name' => 'Cayman Islands'],
            ['countryCode' => 'CF', 'name' => 'Central African Republic'],
            ['countryCode' => 'TD', 'name' => 'Chad'],
            ['countryCode' => 'CL', 'name' => 'Chile'],
            ['countryCode' => 'CN', 'name' => 'China'],
            ['countryCode' => 'CO', 'name' => 'Colombia'],
            ['countryCode' => 'KM', 'name' => 'Comoros'],
            ['countryCode' => 'CD', 'name' => 'DR Congo'],
            ['countryCode' => 'CG', 'name' => 'Congo'],
            ['countryCode' => 'CR', 'name' => 'Costa Rica'],
            ['countryCode' => 'CI', 'name' => 'Côte d\'Ivoire'],
            ['countryCode' => 'HR', 'name' => 'Croatia'],
            ['countryCode' => 'CU', 'name' => 'Cuba'],
            ['countryCode' => 'CY', 'name' => 'Cyprus'],
            ['countryCode' => 'CZ', 'name' => 'Czechia'],
            ['countryCode' => 'DK', 'name' => 'Denmark'],
            ['countryCode' => 'DJ', 'name' => 'Djibouti'],
            ['countryCode' => 'DM', 'name' => 'Dominica'],
            ['countryCode' => 'DO', 'name' => 'Dominican Republic'],
            ['countryCode' => 'EC', 'name' => 'Ecuador'],
            ['countryCode' => 'EG', 'name' => 'Egypt'],
            ['countryCode' => 'SV', 'name' => 'El Salvador'],
            ['countryCode' => 'GQ', 'name' => 'Equatorial Guinea'],
            ['countryCode' => 'ER', 'name' => 'Eritrea'],
            ['countryCode' => 'EE', 'name' => 'Estonia'],
            ['countryCode' => 'SZ', 'name' => 'Eswatini'],
            ['countryCode' => 'ET', 'name' => 'Ethiopia'],
            ['countryCode' => 'FJ', 'name' => 'Fiji'],
            ['countryCode' => 'FI', 'name' => 'Finland'],
            ['countryCode' => 'FR', 'name' => 'France'],
            ['countryCode' => 'GA', 'name' => 'Gabon'],
            ['countryCode' => 'GM', 'name' => 'Gambia'],
            ['countryCode' => 'GE', 'name' => 'Georgia'],
            ['countryCode' => 'DE', 'name' => 'Germany'],
            ['countryCode' => 'GH', 'name' => 'Ghana'],
            ['countryCode' => 'GR', 'name' => 'Greece'],
            ['countryCode' => 'GD', 'name' => 'Grenada'],
            ['countryCode' => 'GU', 'name' => 'Guam'],
            ['countryCode' => 'GT', 'name' => 'Guatemala'],
            ['countryCode' => 'GN', 'name' => 'Guinea'],
            ['countryCode' => 'GW', 'name' => 'Guinea-Bissau'],
            ['countryCode' => 'GY', 'name' => 'Guyana'],
            ['countryCode' => 'HT', 'name' => 'Haiti'],
            ['countryCode' => 'HN', 'name' => 'Honduras'],
            ['countryCode' => 'HK', 'name' => 'Hong Kong'],
            ['countryCode' => 'HU', 'name' => 'Hungary'],
            ['countryCode' => 'IS', 'name' => 'Iceland'],
            ['countryCode' => 'IN', 'name' => 'India'],
            ['countryCode' => 'ID', 'name' => 'Indonesia'],
            ['countryCode' => 'IR', 'name' => 'Iran'],
            ['countryCode' => 'IQ', 'name' => 'Iraq'],
            ['countryCode' => 'IE', 'name' => 'Ireland'],
            ['countryCode' => 'IL', 'name' => 'Israel'],
            ['countryCode' => 'IT', 'name' => 'Italy'],
            ['countryCode' => 'JM', 'name' => 'Jamaica'],
            ['countryCode' => 'JP', 'name' => 'Japan'],
            ['countryCode' => 'JO', 'name' => 'Jordan'],
            ['countryCode' => 'KZ', 'name' => 'Kazakhstan'],
            ['countryCode' => 'KE', 'name' => 'Kenya'],
            ['countryCode' => 'KI', 'name' => 'Kiribati'],
            ['countryCode' => 'KP', 'name' => 'North Korea'],
            ['countryCode' => 'KR', 'name' => 'South Korea'],
            ['countryCode' => 'KW', 'name' => 'Kuwait'],
            ['countryCode' => 'KG', 'name' => 'Kyrgyzstan'],
            ['countryCode' => 'LA', 'name' => 'Laos'],
            ['countryCode' => 'LV', 'name' => 'Latvia'],
            ['countryCode' => 'LB', 'name' => 'Lebanon'],
            ['countryCode' => 'LS', 'name' => 'Lesotho'],
            ['countryCode' => 'LR', 'name' => 'Liberia'],
            ['countryCode' => 'LY', 'name' => 'Libya'],
            ['countryCode' => 'LI', 'name' => 'Liechtenstein'],
            ['countryCode' => 'LT', 'name' => 'Lithuania'],
            ['countryCode' => 'LU', 'name' => 'Luxembourg'],
            ['countryCode' => 'MO', 'name' => 'Macao'],
            ['countryCode' => 'MG', 'name' => 'Madagascar'],
            ['countryCode' => 'MW', 'name' => 'Malawi'],
            ['countryCode' => 'MY', 'name' => 'Malaysia'],
            ['countryCode' => 'MV', 'name' => 'Maldives'],
            ['countryCode' => 'ML', 'name' => 'Mali'],
            ['countryCode' => 'MT', 'name' => 'Malta'],
            ['countryCode' => 'MH', 'name' => 'Marshall Islands'],
            ['countryCode' => 'MR', 'name' => 'Mauritania'],
            ['countryCode' => 'MU', 'name' => 'Mauritius'],
            ['countryCode' => 'MX', 'name' => 'Mexico'],
            ['countryCode' => 'FM', 'name' => 'Micronesia'],
            ['countryCode' => 'MD', 'name' => 'Moldova'],
            ['countryCode' => 'MC', 'name' => 'Monaco'],
            ['countryCode' => 'MN', 'name' => 'Mongolia'],
            ['countryCode' => 'ME', 'name' => 'Montenegro'],
            ['countryCode' => 'MS', 'name' => 'Montserrat'],
            ['countryCode' => 'MA', 'name' => 'Morocco'],
            ['countryCode' => 'MZ', 'name' => 'Mozambique'],
            ['countryCode' => 'MM', 'name' => 'Myanmar'],
            ['countryCode' => 'NA', 'name' => 'Namibia'],
            ['countryCode' => 'NR', 'name' => 'Nauru'],
            ['countryCode' => 'NP', 'name' => 'Nepal'],
            ['countryCode' => 'NL', 'name' => 'Netherlands'],
            ['countryCode' => 'NZ', 'name' => 'New Zealand'],
            ['countryCode' => 'NI', 'name' => 'Nicaragua'],
            ['countryCode' => 'NE', 'name' => 'Niger'],
            ['countryCode' => 'NG', 'name' => 'Nigeria'],
            ['countryCode' => 'MK', 'name' => 'North Macedonia'],
            ['countryCode' => 'NO', 'name' => 'Norway'],
            ['countryCode' => 'OM', 'name' => 'Oman'],
            ['countryCode' => 'PW', 'name' => 'Palau'],
            ['countryCode' => 'PS', 'name' => 'Palestine'],
            ['countryCode' => 'PA', 'name' => 'Panama'],
            ['countryCode' => 'PG', 'name' => 'Papua New Guinea'],
            ['countryCode' => 'PY', 'name' => 'Paraguay'],
            ['countryCode' => 'PE', 'name' => 'Peru'],
            ['countryCode' => 'PH', 'name' => 'Philippines'],
            ['countryCode' => 'PL', 'name' => 'Poland'],
            ['countryCode' => 'PT', 'name' => 'Portugal'],
            ['countryCode' => 'PR', 'name' => 'Puerto Rico'],
            ['countryCode' => 'QA', 'name' => 'Qatar'],
            ['countryCode' => 'RO', 'name' => 'Romania'],
            ['countryCode' => 'RU', 'name' => 'Russia'],
            ['countryCode' => 'RW', 'name' => 'Rwanda'],
            ['countryCode' => 'KN', 'name' => 'Saint Kitts and Nevis'],
            ['countryCode' => 'LC', 'name' => 'Saint Lucia'],
            ['countryCode' => 'VC', 'name' => 'Saint Vincent and the Grenadines'],
            ['countryCode' => 'WS', 'name' => 'Samoa'],
            ['countryCode' => 'SM', 'name' => 'San Marino'],
            ['countryCode' => 'ST', 'name' => 'São Tomé and Príncipe'],
            ['countryCode' => 'SA', 'name' => 'Saudi Arabia'],
            ['countryCode' => 'SN', 'name' => 'Senegal'],
            ['countryCode' => 'RS', 'name' => 'Serbia'],
            ['countryCode' => 'SC', 'name' => 'Seychelles'],
            ['countryCode' => 'SL', 'name' => 'Sierra Leone'],
            ['countryCode' => 'SG', 'name' => 'Singapore'],
            ['countryCode' => 'SK', 'name' => 'Slovakia'],
            ['countryCode' => 'SI', 'name' => 'Slovenia'],
            ['countryCode' => 'SB', 'name' => 'Solomon Islands'],
            ['countryCode' => 'SO', 'name' => 'Somalia'],
            ['countryCode' => 'ZA', 'name' => 'South Africa'],
            ['countryCode' => 'SS', 'name' => 'South Sudan'],
            ['countryCode' => 'ES', 'name' => 'Spain'],
            ['countryCode' => 'LK', 'name' => 'Sri Lanka'],
            ['countryCode' => 'SD', 'name' => 'Sudan'],
            ['countryCode' => 'SR', 'name' => 'Suriname'],
            ['countryCode' => 'SE', 'name' => 'Sweden'],
            ['countryCode' => 'CH', 'name' => 'Switzerland'],
            ['countryCode' => 'SY', 'name' => 'Syria'],
            ['countryCode' => 'TW', 'name' => 'Taiwan'],
            ['countryCode' => 'TJ', 'name' => 'Tajikistan'],
            ['countryCode' => 'TZ', 'name' => 'Tanzania'],
            ['countryCode' => 'TH', 'name' => 'Thailand'],
            ['countryCode' => 'TL', 'name' => 'Timor-Leste'],
            ['countryCode' => 'TG', 'name' => 'Togo'],
            ['countryCode' => 'TO', 'name' => 'Tonga'],
            ['countryCode' => 'TT', 'name' => 'Trinidad and Tobago'],
            ['countryCode' => 'TN', 'name' => 'Tunisia'],
            ['countryCode' => 'TR', 'name' => 'Türkiye'],
            ['countryCode' => 'TM', 'name' => 'Turkmenistan'],
            ['countryCode' => 'TV', 'name' => 'Tuvalu'],
            ['countryCode' => 'UG', 'name' => 'Uganda'],
            ['countryCode' => 'UA', 'name' => 'Ukraine'],
            ['countryCode' => 'AE', 'name' => 'United Arab Emirates'],
            ['countryCode' => 'GB', 'name' => 'United Kingdom'],
            ['countryCode' => 'US', 'name' => 'United States'],
            ['countryCode' => 'UY', 'name' => 'Uruguay'],
            ['countryCode' => 'UZ', 'name' => 'Uzbekistan'],
            ['countryCode' => 'VU', 'name' => 'Vanuatu'],
            ['countryCode' => 'VE', 'name' => 'Venezuela'],
            ['countryCode' => 'VN', 'name' => 'Vietnam'],
            ['countryCode' => 'EH', 'name' => 'Western Sahara'],
            ['countryCode' => 'YE', 'name' => 'Yemen'],
            ['countryCode' => 'ZM', 'name' => 'Zambia'],
            ['countryCode' => 'ZW', 'name' => 'Zimbabwe'],
        ];

        // Normalize country structure to be robust against both legacy and new key schemas
        $countries = array_map(function ($c) {
            $code = $c['countryCode'] ?? $c['key'] ?? '';
            $name = $c['name'] ?? $c['value'] ?? '';
            return [
                'countryCode' => $code,
                'name' => $name,
                'key' => $code,
                'value' => $name,
            ];
        }, $countries);

        // Sort countries alphabetically by name
        usort($countries, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return view(
            'settings.holidays',
            compact('weekHolidays', 'publicHolidays', 'countries', 'years')
        );
    }

    /**
     * Store a newly created company holiday, or import public holidays.
     */
    public function store(Request $request)
    {
        if ($request->has('country')) {
            $currentYear = (int) date('Y');
            $request->validate([
                'country' => 'required|string|size:2',
                'year' => 'required|integer|between:' . ($currentYear - 5) . ',' . ($currentYear + 10),
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

                    $msg = "Successfully imported {$importedCount} new holidays for {$country} ({$year})!";
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
