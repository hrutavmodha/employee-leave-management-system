<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ELMS Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-blue-100 p-6">
                    <div class="text-sm font-medium text-gray-500 truncate">Remaining Leave Days</div>
                    <div class="mt-1 text-3xl font-black text-blue-600">{{ $stats['remaining'] }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-yellow-100 p-6">
                    <div class="text-sm font-medium text-gray-500 truncate">My Pending Requests</div>
                    <div class="mt-1 text-3xl font-black text-yellow-600">{{ $stats['pending'] }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-green-100 p-6">
                    <div class="text-sm font-medium text-gray-500 truncate">Approved Leaves (Year)</div>
                    <div class="mt-1 text-3xl font-black text-green-600">{{ $stats['approved'] }}</div>
                </div>
            </div>

            @if(Auth::user()->isManager() || Auth::user()->isAdmin())
            <!-- Supervisor Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Manager Dashboard</h3>
                        <p class="text-sm text-gray-600">You have <span class="font-bold text-indigo-600">{{ $pendingApprovals }}</span> pending leave applications to review.</p>
                    </div>
                    <a href="{{ route('approvals.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                        View Approvals
                    </a>
                </div>
            </div>
            @endif

            <!-- Quick Links -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <h3 class="font-bold text-gray-700 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('leaves.create') }}" class="bg-blue-50 text-blue-700 px-4 py-3 rounded-lg border border-blue-100 hover:bg-blue-100 transition flex items-center">
                            <span class="mr-2">📄</span> Apply for New Leave
                        </a>
                        <a href="{{ route('leaves.index') }}" class="bg-gray-50 text-gray-700 px-4 py-3 rounded-lg border border-gray-100 hover:bg-gray-100 transition flex items-center">
                            <span class="mr-2">🕒</span> View Leave History
                        </a>
                        @if(Auth::user()->isAdmin())
                        <a href="{{ route('employees.create') }}" class="bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-100 hover:bg-green-100 transition flex items-center">
                            <span class="mr-2">👤</span> Add New Employee
                        </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
