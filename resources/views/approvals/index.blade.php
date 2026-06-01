<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pending Approvals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 shadow-sm rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($pendingRequests as $request)
                                <tr class="hover:bg-gray-50 transition duration-150 border-b border-gray-100 last:border-0">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $request->user->department->name ?? 'No Dept' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->leaveType->name }} ({{ $request->days_requested }} Days)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->start_date->format('M d') }} - {{ $request->end_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ $request->reason }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" action="" id="approval-form-{{ $request->id }}" class="flex flex-col space-y-2">
                                            @csrf
                                            <input type="text" name="manager_comment" placeholder="Add comment..." class="text-xs border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                            <div class="flex space-x-2">
                                                <button type="button" 
                                                        onclick="submitApproval({{ $request->id }}, 'approve')" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition">
                                                    Approve
                                                </button>
                                                <button type="button" 
                                                        onclick="submitApproval({{ $request->id }}, 'reject')" 
                                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition">
                                                    Reject
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 whitespace-nowrap text-center text-gray-500 font-medium">
                                        No pending leave requests found.
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

    <script>
        function submitApproval(id, action) {
            const form = document.getElementById('approval-form-' + id);
            if (action === 'approve') {
                form.action = "{{ url('approvals') }}/" + id + "/approve";
            } else {
                const comment = form.querySelector('input[name="manager_comment"]').value;
                if (!comment) {
                    alert('Comment is required for rejection.');
                    return;
                }
                form.action = "{{ url('approvals') }}/" + id + "/reject";
            }
            form.submit();
        }
    </script>
</x-app-layout>
