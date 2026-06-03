<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Employee Directory') }}
            </h2>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('employees.create') }}" class="bg-blue-600 dark:bg-slate-100 hover:bg-blue-700 dark:hover:bg-white text-white dark:text-slate-900 font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                + Add New Employee
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12" x-data="{ selectedEmployee: null, openDetails: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-center text-gray-600 dark:text-gray-400 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Browse and search the company directory. View designation, manager relations, and department assignments, or <strong>add new personnel</strong>.</p>
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-950/40 border-l-4 border-green-500 dark:border-green-600 text-green-700 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-950/40 border-l-4 border-red-500 dark:border-red-600 text-red-700 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Employee Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    @if(Auth::user()->isAdmin())
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse ($employees as $employee)
                                <tr @click="selectedEmployee = {{ json_encode([
                                    'first_name' => $employee->first_name,
                                    'last_name' => $employee->last_name,
                                    'name' => $employee->name,
                                    'role' => $employee->role,
                                    'department' => $employee->department->name ?? 'N/A',
                                    'manager' => $employee->manager->name ?? 'N/A',
                                    'designation' => $employee->designation ?? 'N/A',
                                    'joining_date' => $employee->joining_date ? $employee->joining_date->format('F d, Y') : 'N/A',
                                    'status' => $employee->status,
                                    'profile_picture' => $employee->profile_picture ? asset('storage/' . $employee->profile_picture) : null,
                                    'initials' => strtoupper(substr($employee->first_name, 0, 1) . (substr($employee->last_name, 0, 1) ?: ''))
                                ]) }}; openDetails = true" class="hover:bg-blue-50/50 dark:hover:bg-slate-700/50 cursor-pointer transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                @if($employee->profile_picture)
                                                    <img class="h-10 w-10 rounded-full object-cover border border-black/20 dark:border-white/30 shadow shadow-black/20 dark:shadow-white/10" src="{{ asset('storage/' . $employee->profile_picture) }}" alt="{{ $employee->name }}">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm border border-black/20 dark:border-white/30 shadow shadow-black/20 dark:shadow-white/10">
                                                        {{ strtoupper(substr($employee->first_name, 0, 1) . (substr($employee->last_name, 0, 1) ?: '')) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $employee->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $employee->designation }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $employee->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50">
                                            {{ $employee->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $employee->department->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $employee->status === 'Active' ? 'bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300' }}">
                                            {{ $employee->status }}
                                        </span>
                                    </td>
                                    @if(Auth::user()->isAdmin())
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" @click.stop>
                                        <div class="flex space-x-3 items-center">
                                            @if ($employee->id !== Auth::id())
                                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this employee?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-950 dark:hover:text-red-300 font-bold text-xs uppercase tracking-wider bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/50 px-3 py-1.5 rounded border border-red-200 dark:border-red-900/50 transition">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->isAdmin() ? 6 : 5 }}" class="px-6 py-10 whitespace-nowrap text-center text-gray-500 dark:text-gray-400">
                                        No employees found. @if(Auth::user()->isAdmin()) <a href="{{ route('employees.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Add one now</a>. @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Details Modal -->
        <div x-show="openDetails" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak
             style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="openDetails"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="openDetails = false"
                     class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50 backdrop-blur-sm" 
                     aria-hidden="true"></div>

                <!-- Helper element to center the modal contents -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="openDetails"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-slate-700">
                    
                    <!-- Close Button -->
                    <div class="absolute top-4 right-4">
                       <button @click="openDetails = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-slate-700 focus:outline-none">
                           <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                           </svg>
                       </button>
                    </div>

                    <div class="bg-white dark:bg-slate-800 px-6 pt-8 pb-6 sm:px-8">
                        <template x-if="selectedEmployee">
                            <div class="space-y-6">
                                <!-- Profile Image/Header -->
                                <div class="flex flex-col items-center text-center pb-4 border-b border-gray-100 dark:border-slate-700">
                                    <div class="relative mb-4">
                                        <template x-if="selectedEmployee.profile_picture">
                                            <img :src="selectedEmployee.profile_picture" alt="Profile Picture" class="w-24 h-24 rounded-full object-cover border-4 border-black/10 dark:border-white/20 shadow-lg shadow-black/20 dark:shadow-white/10">
                                        </template>
                                        <template x-if="!selectedEmployee.profile_picture">
                                            <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-white font-extrabold text-2xl border-4 border-black/10 dark:border-white/20 shadow-lg shadow-black/20 dark:shadow-white/10">
                                                <span x-text="selectedEmployee.initials"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 leading-7" x-text="selectedEmployee.name"></h3>
                                    <p class="text-sm font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mt-1" x-text="selectedEmployee.designation"></p>
                                </div>

                                <!-- Details Grid -->
                                <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">First Name</span>
                                        <span class="block font-semibold text-gray-800 dark:text-gray-200 mt-1" x-text="selectedEmployee.first_name"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Last Name</span>
                                        <span class="block font-semibold text-gray-800 dark:text-gray-200 mt-1" x-text="selectedEmployee.last_name"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">System Role</span>
                                        <span class="block mt-1">
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-md bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50" x-text="selectedEmployee.role"></span>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Department</span>
                                        <span class="block font-semibold text-gray-800 dark:text-gray-200 mt-1" x-text="selectedEmployee.department"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Reporting Manager</span>
                                        <span class="block font-semibold text-gray-800 dark:text-gray-200 mt-1" x-text="selectedEmployee.manager"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Joining Date</span>
                                        <span class="block font-semibold text-gray-800 dark:text-gray-200 mt-1" x-text="selectedEmployee.joining_date"></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Status</span>
                                        <span class="block mt-1">
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="selectedEmployee.status === 'Active' ? 'bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300'"
                                                  x-text="selectedEmployee.status"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-slate-750/70 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-slate-700 rounded-b-2xl">
                        <button type="button" 
                                @click="openDetails = false"
                                class="w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
