<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee Directory') }}
            </h2>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('employees.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                + Add New Employee
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12" x-data="{ selectedEmployee: null, openDetails: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-black text-center text-gray-900 mb-6">Employees</h1>
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    @if(Auth::user()->isAdmin())
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
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
                                ]) }}; openDetails = true" class="hover:bg-blue-50/50 cursor-pointer transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                @if($employee->profile_picture)
                                                    <img class="h-10 w-10 rounded-full object-cover border border-gray-200" src="{{ asset('storage/' . $employee->profile_picture) }}" alt="{{ $employee->name }}">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm">
                                                        {{ strtoupper(substr($employee->first_name, 0, 1) . (substr($employee->last_name, 0, 1) ?: '')) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">{{ $employee->name }}</div>
                                                <div class="text-xs text-gray-500 font-medium">{{ $employee->designation }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $employee->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $employee->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $employee->department->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $employee->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
                                                    <button type="submit" class="text-red-600 hover:text-red-950 font-bold text-xs uppercase tracking-wider bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded border border-red-200 transition">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->isAdmin() ? 6 : 5 }}" class="px-6 py-10 whitespace-nowrap text-center text-gray-500">
                                        No employees found. @if(Auth::user()->isAdmin()) <a href="{{ route('employees.create') }}" class="text-blue-600 hover:underline">Add one now</a>. @endif
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
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    
                    <!-- Close Button -->
                    <div class="absolute top-4 right-4">
                       <button @click="openDetails = false" class="text-gray-400 hover:text-gray-600 transition p-1.5 rounded-full hover:bg-gray-100 focus:outline-none">
                           <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                           </svg>
                       </button>
                    </div>

                    <div class="bg-white px-6 pt-8 pb-6 sm:px-8">
                        <template x-if="selectedEmployee">
                            <div class="space-y-6">
                                <!-- Profile Image/Header -->
                                <div class="flex flex-col items-center text-center pb-4 border-b border-gray-100">
                                    <div class="relative mb-4">
                                        <template x-if="selectedEmployee.profile_picture">
                                            <img :src="selectedEmployee.profile_picture" alt="Profile Picture" class="w-24 h-24 rounded-full object-cover border-4 border-blue-50 shadow-md">
                                        </template>
                                        <template x-if="!selectedEmployee.profile_picture">
                                            <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-white font-extrabold text-2xl border-4 border-blue-50 shadow-md">
                                                <span x-text="selectedEmployee.initials"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-900 leading-7" x-text="selectedEmployee.name"></h3>
                                    <p class="text-sm font-semibold text-blue-600 uppercase tracking-wider mt-1" x-text="selectedEmployee.designation"></p>
                                </div>

                                <!-- Details Grid -->
                                <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">First Name</span>
                                        <span class="block font-semibold text-gray-800 mt-1" x-text="selectedEmployee.first_name"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Last Name</span>
                                        <span class="block font-semibold text-gray-800 mt-1" x-text="selectedEmployee.last_name"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">System Role</span>
                                        <span class="block mt-1">
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-md bg-blue-50 text-blue-700 border border-blue-100" x-text="selectedEmployee.role"></span>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Department</span>
                                        <span class="block font-semibold text-gray-800 mt-1" x-text="selectedEmployee.department"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Reporting Manager</span>
                                        <span class="block font-semibold text-gray-800 mt-1" x-text="selectedEmployee.manager"></span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Joining Date</span>
                                        <span class="block font-semibold text-gray-800 mt-1" x-text="selectedEmployee.joining_date"></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-400">Status</span>
                                        <span class="block mt-1">
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="selectedEmployee.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                  x-text="selectedEmployee.status"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse border-t border-gray-100 rounded-b-2xl">
                        <button type="button" 
                                @click="openDetails = false"
                                class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
