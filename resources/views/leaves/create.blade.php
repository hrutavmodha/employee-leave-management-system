<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Apply for Leave') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-center text-gray-600 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Submit a new leave request. Choose a leave type, enter your desired dates, and attach <strong>supporting documentation</strong> if required.</p>
            
            <!-- Balance Summary -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6">
                    <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">My Available Balance</h3>
                    <div class="flex flex-wrap gap-4">
                        @foreach($balances as $bal)
                            <div class="bg-blue-50 dark:bg-slate-900/50 border border-blue-100 dark:border-slate-700 rounded-lg p-3">
                                <div class="text-xs text-blue-600 dark:text-blue-400 font-bold">{{ $bal->leaveType->name }}</div>
                                <div class="text-lg font-black text-blue-800 dark:text-blue-300">{{ $bal->remaining_days }} Days</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('leaves.store') }}" enctype="multipart/form-data" class="space-y-6 max-w-xl">
                        @csrf

                        <!-- Leave Type -->
                        <div>
                            <x-input-label for="leave_type_id" :value="__('Leave Type')" />
                            <select id="leave_type_id" name="leave_type_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="" class="dark:bg-slate-900">Select Leave Type</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }} class="dark:bg-slate-900">
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('leave_type_id')" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Start Date -->
                            <div>
                                <x-input-label for="start_date" :value="__('Start Date')" />
                                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                            </div>

                            <!-- End Date -->
                            <div>
                                <x-input-label for="end_date" :value="__('End Date')" />
                                <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <x-input-label for="reason" :value="__('Reason for Leave')" />
                            <textarea id="reason" name="reason" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="4" required>{{ old('reason') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                        </div>

                        <!-- Attachment -->
                        <div>
                            <x-input-label for="attachment" :value="__('Attachment (Optional - PDF, JPG, PNG)')" />
                            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-slate-700 file:text-blue-700 dark:file:text-gray-300 hover:file:bg-blue-100 dark:hover:file:bg-slate-600" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Max size: 2MB</p>
                            <x-input-error class="mt-2" :messages="$errors->get('attachment')" />
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <x-primary-button>{{ __('Submit Application') }}</x-primary-button>
                            <a href="{{ route('leaves.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 underline">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
