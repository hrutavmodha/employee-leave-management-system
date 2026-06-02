<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Leave Approval Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-black text-center text-gray-900 mb-6">Pending Approvals</h1>
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leave Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action Feedback</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Final Decision</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($pendingRequests as $request)
                                <tr class="hover:bg-gray-50 transition border-b last:border-0">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-black text-gray-900">{{ $request->user->name }}</div>
                                        <div class="text-xs text-gray-600 italic">"{{ $request->reason }}"</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-bold text-xs">{{ $request->leaveType->name }}</span>
                                        <div class="text-sm mt-1 font-bold">{{ $request->days_requested }} Days</div>
                                        <div class="text-xs text-gray-500">{{ $request->start_date->format('M d') }} - {{ $request->end_date->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <!-- Form start -->
                                        <form id="approval-{{ $request->id }}" method="POST" class="space-y-2">
                                            @csrf
                                            <input type="text" name="manager_comment" placeholder="Optional feedback..." class="text-xs border-gray-300 rounded w-full focus:ring-blue-500">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button type="submit" form="approval-{{ $request->id }}" formaction="{{ route('approvals.approve', $request) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-xs font-bold uppercase transition">
                                                Approve
                                            </button>
                                            <button type="submit" form="approval-{{ $request->id }}" formaction="{{ route('approvals.reject', $request) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-xs font-bold uppercase transition">
                                                Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">All caught up! No pending leave requests to review.</td>
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
