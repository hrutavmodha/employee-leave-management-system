<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Dark Mode Checker & Toggler -->
        <script>
            // Set initial theme
            if (localStorage.getItem('color-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Global toggle function
            window.toggleDarkMode = function() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('color-theme', isDark ? 'dark' : 'light');
                
                // Sync all icons
                document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
                    const darkIcon = btn.querySelector('.theme-toggle-dark-icon');
                    const lightIcon = btn.querySelector('.theme-toggle-light-icon');
                    if (darkIcon && lightIcon) {
                        if (isDark) {
                            lightIcon.classList.remove('hidden');
                            darkIcon.classList.add('hidden');
                        } else {
                            darkIcon.classList.remove('hidden');
                            lightIcon.classList.add('hidden');
                        }
                    }
                });
            };

            // Sync icons on page load
            document.addEventListener('DOMContentLoaded', () => {
                const isDark = document.documentElement.classList.contains('dark');
                document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
                    const darkIcon = btn.querySelector('.theme-toggle-dark-icon');
                    const lightIcon = btn.querySelector('.theme-toggle-light-icon');
                    if (darkIcon && lightIcon) {
                        if (isDark) {
                            lightIcon.classList.remove('hidden');
                            darkIcon.classList.add('hidden');
                        } else {
                            darkIcon.classList.remove('hidden');
                            lightIcon.classList.add('hidden');
                        }
                    }
                });
            });
        </script>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-slate-900">
        <div class="min-h-screen bg-gray-100 dark:bg-slate-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-slate-800 shadow dark:shadow-none border-b border-gray-100 dark:border-slate-700">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
