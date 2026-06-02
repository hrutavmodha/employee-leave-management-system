<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Leave Type') }}: {{ $leaveType->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-black text-center text-gray-900 mb-6">Edit Leave Type</h1>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('leave-types.update', $leaveType) }}" class="space-y-6 max-w-xl">
                        @csrf
                        @method('PATCH')

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Leave Type Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $leaveType->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <!-- Allowed Days -->
                        <div>
                            <x-input-label for="allowed_days" :value="__('Allowed Days Per Year')" />
                            <x-text-input id="allowed_days" name="allowed_days" type="number" class="mt-1 block w-full" :value="old('allowed_days', $leaveType->allowed_days)" required min="0" />
                            <x-input-error class="mt-2" :messages="$errors->get('allowed_days')" />
                        </div>

                        <!-- Carry Forward -->
                        <div class="block">
                            <label for="carry_forward" class="inline-flex items-center">
                                <input id="carry_forward" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="carry_forward" value="1" {{ old('carry_forward', $leaveType->carry_forward) ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-gray-600">{{ __('Allow Carry Forward to Next Year') }}</span>
                            </label>
                            <x-input-error class="mt-2" :messages="$errors->get('carry_forward')" />
                        </div>

                        <!-- Description -->
                        <div>
                            <x-input-label for="description" :value="__('Description (Optional)')" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3">{{ old('description', $leaveType->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Update Leave Type') }}</x-primary-button>
                            <a href="{{ route('leave-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
