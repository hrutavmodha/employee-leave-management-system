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
                document.documentElement.classList.add('theme-toggling');
                
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

                setTimeout(() => {
                    document.documentElement.classList.remove('theme-toggling');
                }, 500);
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
    <body class="font-sans text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-slate-900 antialiased relative">
        <div class="absolute top-4 right-4">
            <button type="button" onclick="toggleDarkMode()" class="theme-toggle-btn text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700/50 focus:outline-none rounded-lg p-2 transition duration-150 ease-in-out">
                <svg class="theme-toggle-dark-icon w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                </svg>
                <svg class="theme-toggle-light-icon hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <div class="min-h-dvh flex flex-col justify-center items-center px-4 sm:px-0 bg-gray-100 dark:bg-slate-900">
            <!-- Logo Removed -->

            <div class="w-full sm:max-w-md px-6 py-4 bg-white dark:bg-slate-800 border dark:border-slate-700 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
