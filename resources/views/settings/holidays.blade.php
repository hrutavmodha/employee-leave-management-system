<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Holiday & Weekend Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-center text-gray-600 dark:text-gray-400 mb-8 max-w-3xl mx-auto text-sm leading-relaxed">
                Configure your company's non-working days. Select which days of the week are default rest days, and specify official company holidays using the calendar datepicker.
            </p>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 dark:bg-green-950/40 border-l-4 border-green-500 dark:border-green-600 text-green-700 dark:text-green-300 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-950/40 border-l-4 border-red-500 dark:border-red-600 text-red-700 dark:text-red-300 rounded shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left: Week Holidays Configuration -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow sm:rounded-lg border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-slate-700 pb-3 mb-4">
                        {{ __('Weekly Rest Days') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">
                        Select the days of the week when the company is closed. Leaves applied on these days will not consume leave balance.
                    </p>

                    <form action="{{ route('settings.week_holidays.update') }}" method="POST">
                        @csrf
                        <div class="space-y-3 mb-6">
                            @php
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    0 => 'Sunday',
                                ];
                            @endphp

                            @foreach($daysOfWeek as $value => $name)
                                <label class="flex items-center space-x-3 text-sm cursor-pointer select-none text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="week_holidays[]" value="{{ $value }}"
                                           {{ in_array($value, $weekHolidays) ? 'checked' : '' }}
                                           class="rounded border-gray-350 dark:border-slate-650 text-blue-600 focus:ring-blue-500 dark:focus:ring-offset-slate-800 bg-gray-50 dark:bg-slate-900">
                                    <span>{{ __($name) }}</span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="w-full bg-blue-600 dark:bg-slate-100 hover:bg-blue-700 dark:hover:bg-white text-white dark:text-slate-900 font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                            {{ __('Save Weekly Settings') }}
                        </button>
                    </form>

                    <div class="mt-8 border-t border-gray-200 dark:border-slate-700 pt-6">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 pb-3 mb-4">
                            {{ __('Auto-Import Holidays') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">
                            Automatically fetch and populate public holidays for a country and year using the public holiday registry.
                        </p>

                        <form action="{{ route('settings.holidays.import') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                    {{ __('Country') }}
                                </label>
                                <select name="country" required
                                        class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    @foreach($countries as $c)
                                        @php
                                            $cKey = $c['key'] ?? '';
                                            $cValue = $c['value'] ?? '';
                                        @endphp
                                        <option value="{{ $cKey }}" {{ $cKey == 'IN' ? 'selected' : '' }} class="dark:bg-slate-900">
                                            {{ $cValue }} ({{ $cKey }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                    {{ __('Year') }}
                                </label>
                                <select name="year" required
                                        class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                        <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }} class="dark:bg-slate-900">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 dark:bg-slate-100 hover:bg-blue-700 dark:hover:bg-white text-white dark:text-slate-900 font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                                {{ __('Fetch & Import Holidays') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Company Calendar Holidays -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow sm:rounded-lg border border-gray-200 dark:border-slate-700 p-6">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-slate-700 pb-3 mb-4">
                        {{ __('Official Company Holidays') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">
                        Add specific calendar dates as holidays (e.g. New Year, Christmas). These will be excluded from leave durations.
                    </p>

                    <!-- Add Holiday Form -->
                    <form action="{{ route('settings.holidays.store') }}" method="POST" class="space-y-4 mb-8">
                        @csrf
                        <div>
                            <label for="name" class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                {{ __('Holiday Name') }}
                            </label>
                            <input type="text" name="name" id="name" required placeholder="e.g. Christmas Day"
                                   class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date" class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                {{ __('Select Date from Calendar') }}
                            </label>
                            <input type="date" name="date" id="date" required
                                   class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            @error('date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full bg-blue-600 dark:bg-slate-100 hover:bg-blue-700 dark:hover:bg-white text-white dark:text-slate-900 font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                            {{ __('Add Holiday') }}
                        </button>
                    </form>

                    <!-- Holidays List -->
                    <div>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-gray-100 mb-3">
                            {{ __('Scheduled Holidays') }}
                        </h4>
                        @if($publicHolidays->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-gray-400 italic text-center py-4 bg-gray-50 dark:bg-slate-900 rounded">
                                {{ __('No company holidays scheduled.') }}
                            </p>
                        @else
                            <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-slate-700 rounded divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach($publicHolidays as $holiday)
                                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-900/40 hover:bg-gray-100 dark:hover:bg-slate-900 transition duration-150">
                                        <div>
                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                                {{ $holiday->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ $holiday->date->format('M d, Y') }} ({{ $holiday->date->format('l') }})
                                            </div>
                                        </div>
                                        <form action="{{ route('settings.holidays.destroy', $holiday) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to remove this holiday?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-950 dark:hover:text-red-200">
                                                {{ __('Remove') }}
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
