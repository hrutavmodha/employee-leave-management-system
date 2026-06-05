<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Leave Reports Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <p class="text-center text-gray-600 dark:text-gray-400 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Analyze organizational absenteeism trends, monitor department averages, and export historical monthly <strong>leave distribution statistics</strong>.</p>
            
            <!-- Employee Summary Section -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-700 dark:text-gray-200">Employee Leave Summary</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Approved Leaves</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current Balances</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach($employeeStats as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $user->department->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900 dark:text-gray-100">{{ $user->approved_leaves }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        @foreach($user->leaveBalances as $balance)
                                            <span class="inline-block bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 px-2 py-1 rounded mr-1 mb-1 border border-blue-100 dark:border-blue-900/50">
                                                {{ $balance->leaveType->name }}: {{ $balance->remaining_days }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($employeeStats instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        <div class="mt-4">
                            {{ $employeeStats->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Department Stats Section -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-700 dark:text-gray-200">Department Overview</h3>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Leaves</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Appr/Rej</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                @foreach($departmentStats as $dept)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition duration-150">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $dept->name }}</td>
                                    <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">{{ $dept->total_leaves }}</td>
                                    <td class="px-6 py-4 text-center text-gray-900 dark:text-gray-100">
                                        <span class="text-green-600 dark:text-green-400 font-bold">{{ $dept->approved_leaves }}</span> / 
                                        <span class="text-red-600 dark:text-red-400 font-bold">{{ $dept->rejected_leaves }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Approved Leaves -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-700 dark:text-gray-200">Monthly Approved Leaves ({{ date('Y') }})</h3>
                        
                        @php
                            $monthsData = collect(range(1, 12))->mapWithKeys(function ($m) use ($monthlyStats) {
                                $key = str_pad($m, 2, '0', STR_PAD_LEFT);
                                $stat = $monthlyStats->firstWhere('month', $key);
                                return [$key => $stat ? (int)$stat->count : 0];
                            });
                            $maxCount = $monthsData->max() ?: 10;
                            // Ensure grid increments are clean integers
                            $gridSteps = 4;
                            $stepValue = ceil($maxCount / $gridSteps) ?: 1;
                            $chartMax = $stepValue * $gridSteps;
                            
                            $monthNames = [
                                '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                                '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                                '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                            ];
                        @endphp

                        <div class="relative flex flex-col mt-6">
                            <!-- Chart Area: Grid + Bars -->
                            <div class="relative h-48 w-full">
                                <!-- Y-Axis Grid Lines & Labels -->
                                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-0">
                                    @for ($i = $gridSteps; $i >= 0; $i--)
                                        @php
                                            $val = $stepValue * $i;
                                        @endphp
                                        <div class="flex items-center w-full h-0">
                                            <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500 w-6 text-right mr-2">{{ $val }}</span>
                                            <div class="flex-1 border-b border-dashed border-gray-200/80 dark:border-slate-700/50"></div>
                                        </div>
                                    @endfor
                                </div>

                                <!-- Bar Charts Container -->
                                <div class="absolute inset-0 flex items-end justify-between pl-8 pb-0">
                                    @foreach($monthsData as $month => $count)
                                        @php
                                            $percent = $chartMax > 0 ? ($count / $chartMax) * 100 : 0;
                                        @endphp
                                        <div class="flex-1 flex flex-col items-center group h-full justify-end relative">
                                            <!-- Vertical Bar -->
                                            <div class="w-1/2 max-w-[20px] bg-gradient-to-t from-blue-600 to-indigo-500 dark:from-blue-500 dark:to-indigo-400 rounded-t hover:from-blue-500 hover:to-indigo-400 transition-all duration-300 relative cursor-pointer"
                                                 style="height: {{ $percent }}%">
                                                
                                                <!-- Tooltip -->
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none bg-slate-900 dark:bg-slate-950 text-white text-[10px] rounded px-1.5 py-0.5 shadow-md z-30 whitespace-nowrap">
                                                    {{ $count }} Leaves
                                                </div>

                                                @if($count > 0)
                                                    <span class="absolute -top-5 left-1/2 transform -translate-x-1/2 text-[9px] font-bold text-blue-600 dark:text-blue-400">
                                                        {{ $count }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- X-Axis Labels -->
                            <div class="flex justify-between pl-8 mt-2 pt-2 border-t border-gray-200 dark:border-slate-700">
                                @foreach($monthsData as $month => $count)
                                    <div class="flex-1 text-center">
                                        <span class="text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ $monthNames[$month] ?? $month }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
