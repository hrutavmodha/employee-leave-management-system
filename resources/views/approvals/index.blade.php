<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Leave Approval Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-center text-gray-600 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">As a manager or supervisor, you can <strong>review, approve, or reject</strong> leave applications submitted by your team members. Please make sure to <em>verify active leave balances</em> before taking actions.</p>
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

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Leave Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action Feedback</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Final Decision</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse ($pendingRequests as $request)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition border-b dark:border-slate-700 last:border-0">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-black text-gray-900 dark:text-white">{{ $request->user->name }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 italic">"{{ $request->reason }}"</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded-md bg-blue-50 dark:bg-slate-900/50 text-blue-700 dark:text-blue-400 border dark:border-slate-700 font-bold text-xs">{{ $request->leaveType->name }}</span>
                                        <div class="text-sm mt-1 font-bold dark:text-gray-200">{{ $request->days_requested }} Days</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->start_date->format('M d') }} - {{ $request->end_date->format('M d, Y') }}</div>
                                        @if($request->attachments->isNotEmpty())
                                            <div class="mt-2 flex flex-col gap-0.5">
                                                @foreach($request->attachments as $attachment)
                                                    <a href="{{ route('leaves.attachment', [$request, $attachment]) }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                        {{ $attachment->file_name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <!-- Form start -->
                                        <form id="approval-{{ $request->id }}" method="POST" class="space-y-2">
                                            @csrf
                                            <input type="text" name="manager_comment" placeholder="Optional feedback..." class="text-xs border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded w-full focus:ring-blue-500">
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
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">All caught up! No pending leave requests to review.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($pendingRequests->hasPages())
                        <div class="mt-6 dark:text-gray-300">
                            {{ $pendingRequests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
