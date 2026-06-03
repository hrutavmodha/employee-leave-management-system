<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Leave Type Management') }}
            </h2>
            <a href="{{ route('leave-types.create') }}" class="bg-blue-600 dark:bg-slate-100 hover:bg-blue-700 dark:hover:bg-white text-white dark:text-slate-900 font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                + Add Leave Type
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-center text-gray-600 dark:text-gray-400 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Manage active leave types and balance rules. These parameters are used by the system's core leave engine to control <strong>deductions and validation</strong>.</p>
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Leave Type Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Allowed Days</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Carry Forward</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse ($leaveTypes as $type)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $type->name }}
                                        @if($type->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-normal">{{ $type->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $type->allowed_days }} Days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="px-2 py-0.5 rounded text-xs {{ $type->carry_forward ? 'bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-300' }}">
                                            {{ $type->carry_forward ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-4">
                                            <a href="{{ route('leave-types.edit', $type) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</a>
                                            <form action="{{ route('leave-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this leave type?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 whitespace-nowrap text-center text-gray-500 dark:text-gray-400 font-medium">
                                        No leave types found. Start by <a href="{{ route('leave-types.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">adding one</a>.
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
