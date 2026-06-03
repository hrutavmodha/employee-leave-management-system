<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Department Management') }}
            </h2>
            <a href="{{ route('departments.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition ease-in-out duration-150">
                + Add Department
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-black text-center text-gray-900 mb-6">Departments</h1>
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Employees</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($departments as $dept)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold mr-3 border border-indigo-100">
                                                &#x1F3E2;
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ $dept->name }}</div>
                                                @if($dept->description)
                                                    <p class="text-xs text-gray-500 font-normal mt-0.5">{{ $dept->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">
                                        <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $dept->users_count }} Employees
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-4">
                                            <a href="{{ route('departments.edit', $dept) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">Rename / Edit</a>
                                            <form action="{{ route('departments.destroy', $dept) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this department? Employees inside this department will be set to Unassigned.');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 whitespace-nowrap text-center text-gray-500 font-medium">
                                        No departments found. Start by <a href="{{ route('departments.create') }}" class="text-blue-600 hover:underline">adding one</a>.
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
