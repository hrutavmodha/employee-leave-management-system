<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('ELMS Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-center text-gray-600 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Welcome back! Here is an overview of your <strong>employment status, profile details, and quick shortcuts</strong> to apply for leaves or manage records.</p>
            
            <!-- Employee Profile Overview Card -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700 p-6 flex flex-col md:flex-row items-center md:items-start gap-6">
                <!-- Avatar Section -->
                <div class="relative w-24 h-24 shrink-0">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="w-24 h-24 rounded-full object-cover border-4 border-black/10 dark:border-white/20 shadow-lg shadow-black/20 dark:shadow-white/10">
                    @else
                        <div class="w-24 h-24 rounded-full bg-blue-50 dark:bg-slate-700 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-3xl border-4 border-black/10 dark:border-white/20 shadow-lg shadow-black/20 dark:shadow-white/10">
                            {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <!-- Info Details Section -->
                <div class="flex-grow text-center md:text-left space-y-2">
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white leading-none">{{ Auth::user()->name }}</h1>
                        <p class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-1 uppercase tracking-wider">{{ Auth::user()->designation ?? 'Employee' }}</p>
                    </div>

                    <!-- Grid of Information Details -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-2 text-sm">
                        <!-- Department -->
                        <div class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-400 text-lg">&#x1F3E2;</span>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Department</div>
                                <div class="font-semibold dark:text-gray-200">{{ Auth::user()->department->name ?? 'Not Assigned' }}</div>
                            </div>
                        </div>

                        <!-- Manager -->
                        <div class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-400 text-lg">&#x1F464;</span>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Reporting Manager</div>
                                <div class="font-semibold dark:text-gray-200">{{ Auth::user()->manager->name ?? 'None' }}</div>
                            </div>
                        </div>

                        <!-- Joining Date -->
                        <div class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-400 text-lg">&#x1F4C5;</span>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Joining Date</div>
                                <div class="font-semibold dark:text-gray-200">
                                    {{ Auth::user()->joining_date ? Auth::user()->joining_date->format('M d, Y') : 'Not Set' }}
                                </div>
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-400 text-lg">&#x1F6E1;&#xFE0F;</span>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Role</div>
                                <div class="font-semibold dark:text-gray-200">{{ Auth::user()->role }}</div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400">
                            <span class="text-gray-400 text-lg">&#x1F7E2;</span>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Status</div>
                                <div class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 dark:bg-green-950/50 text-green-800 dark:text-green-300 border dark:border-green-800/30">
                                    {{ Auth::user()->status }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-yellow-100 dark:border-yellow-950/30 p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">My Pending Requests</div>
                    <div class="mt-1 text-3xl font-black text-yellow-600 dark:text-yellow-500">{{ $stats['pending'] }}</div>
                </div>

                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-green-100 dark:border-green-950/30 p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Approved Leaves (Year)</div>
                    <div class="mt-1 text-3xl font-black text-green-600 dark:text-green-500">{{ $stats['approved'] }}</div>
                </div>
            </div>

            @if(Auth::user()->isManager() || Auth::user()->isAdmin())
            <!-- Supervisor Actions -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500 dark:border-indigo-400">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manager Dashboard</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">You have <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $pendingApprovals }}</span> pending leave applications to review.</p>
                    </div>
                    <a href="{{ route('approvals.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-slate-100 border border-transparent rounded-md font-semibold text-xs text-white dark:text-slate-900 uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-white transition ease-in-out duration-150">
                        View Approvals
                    </a>
                </div>
            </div>
            @endif

            <!-- Quick Links -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6">
                    <h3 class="font-bold text-gray-700 dark:text-gray-300 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('leaves.create') }}" class="bg-blue-900 dark:bg-slate-100 hover:bg-blue-800 dark:hover:bg-white text-white dark:text-slate-900 font-semibold px-4 py-3 rounded-lg border border-blue-800 dark:border-transparent transition flex items-center shadow-md">
                            <span class="mr-2">&#x1F4C4;</span> Apply for New Leave
                        </a>
                        <a href="{{ route('leaves.index') }}" class="bg-blue-900 dark:bg-slate-100 hover:bg-blue-800 dark:hover:bg-white text-white dark:text-slate-900 font-semibold px-4 py-3 rounded-lg border border-blue-800 dark:border-transparent transition flex items-center shadow-md">
                            <span class="mr-2">&#x1F552;</span> View Leave History
                        </a>
                        @if(Auth::user()->isAdmin())
                        <a href="{{ route('employees.create') }}" class="bg-blue-900 dark:bg-slate-100 hover:bg-blue-800 dark:hover:bg-white text-white dark:text-slate-900 font-semibold px-4 py-3 rounded-lg border border-blue-800 dark:border-transparent transition flex items-center shadow-md">
                            <span class="mr-2">&#x1F464;</span> Add New Employee
                        </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
