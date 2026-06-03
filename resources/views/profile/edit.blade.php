<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-center text-gray-600 dark:text-gray-400 mb-6 max-w-3xl mx-auto text-sm leading-relaxed">Update your account profile. You can upload an avatar picture, edit details, or change your <strong>secure login password</strong>.</p>
            <div class="p-4 sm:p-8 bg-white dark:bg-slate-800 border dark:border-slate-700 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-slate-800 border dark:border-slate-700 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if(Auth::user()->email === 'test@example.com' || Auth::user()->role === 'HR/Admin')
                <div class="p-4 sm:p-8 bg-white dark:bg-slate-800 shadow sm:rounded-lg border-l-4 border-red-500 dark:border-red-600">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
