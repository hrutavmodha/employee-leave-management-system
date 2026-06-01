<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Leave Reports Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Employee Summary Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-700">Employee Leave Summary</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved Leaves</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Balances</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($employeeStats as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->department->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">{{ $user->approved_leaves }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        @foreach($user->leaveBalances as $balance)
                                            <span class="inline-block bg-blue-50 text-blue-700 px-2 py-1 rounded mr-1 mb-1 border border-blue-100">
                                                {{ $balance->leaveType->name }}: {{ $balance->remaining_days }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Department Stats Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-700">Department Overview</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Leaves</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Appr/Rej</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                @foreach($departmentStats as $dept)
                                <tr>
                                    <td class="px-6 py-4 font-medium">{{ $dept->name }}</td>
                                    <td class="px-6 py-4 text-center">{{ $dept->total_leaves }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-green-600 font-bold">{{ $dept->approved_leaves }}</span> / 
                                        <span class="text-red-600 font-bold">{{ $dept->rejected_leaves }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Stats Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-700">Monthly Approved Leaves ({{ date('Y') }})</h3>
                        <div class="space-y-4">
                            @forelse($monthlyStats as $stat)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Month {{ $stat->month }}</span>
                                        <span class="font-bold">{{ $stat->count }} Leaves</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $stat->count * 10) }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 italic">No approved leaves this year yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
