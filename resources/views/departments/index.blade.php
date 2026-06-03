<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Department Management') }}
            </h2>
            <a href="{{ route('departments.create') }}" class="bg-blue-600 dark:bg-slate-100 hover:bg-blue-700 dark:hover:bg-white text-white dark:text-slate-900 font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                + Add Department
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-center text-gray-600 dark:text-gray-400 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Manage your company's departments. You can rename existing divisions or delete them. Note that deleting a department sets all its members to <strong>Unassigned</strong> status.</p>
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-950/40 border-l-4 border-green-500 dark:border-green-600 text-green-700 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Active Employees</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse ($departments as $dept)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 flex items-center justify-center font-bold mr-3 border border-indigo-100 dark:border-indigo-900/50">
                                                &#x1F3E2;
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $dept->name }}</div>
                                                @if($dept->description)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-normal mt-0.5">{{ $dept->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-semibold">
                                        <span class="px-2.5 py-1 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50">
                                            {{ $dept->users_count }} Employees
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-4">
                                            <a href="{{ route('departments.edit', $dept) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-semibold">Rename / Edit</a>
                                            <form action="{{ route('departments.destroy', $dept) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this department? Employees inside this department will be set to Unassigned.');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 font-semibold">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 whitespace-nowrap text-center text-gray-500 dark:text-gray-400 font-medium">
                                        No departments found. Start by <a href="{{ route('departments.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">adding one</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
