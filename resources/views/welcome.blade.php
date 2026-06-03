<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'ELMS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

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
    <body class="bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-gray-100 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col font-sans">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    <!-- Theme Toggle Button -->
                    <button type="button" onclick="toggleDarkMode()" class="theme-toggle-btn text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none rounded-lg p-2 transition duration-150 ease-in-out">
                        <svg class="theme-toggle-dark-icon w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg class="theme-toggle-light-icon hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                        </svg>
                    </button>

                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-2 border border-blue-600 bg-blue-600 dark:bg-slate-100 text-white dark:text-slate-900 dark:border-transparent rounded-md text-sm font-bold shadow-sm hover:bg-blue-700 dark:hover:bg-white transition"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-2 text-gray-700 dark:text-gray-300 font-semibold hover:text-blue-600 dark:hover:text-blue-400 transition"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-2 border border-blue-600 text-blue-600 dark:text-blue-400 rounded-md text-sm font-bold hover:bg-blue-50 dark:hover:bg-slate-800 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex items-center justify-center w-full grow">
            <main class="flex max-w-[335px] w-full flex-col lg:max-w-4xl lg:flex-row bg-white dark:bg-slate-800 shadow-xl rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-700">
                <div class="flex-1 p-8 lg:p-16">
                    <div class="mb-8">
                        <span class="text-blue-600 dark:text-blue-400 font-black text-3xl tracking-tighter">ELMS</span>
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-4 tracking-tight">Employee Leave Management System</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">Streamline your organization's leave requests, approvals, and reporting in one central place.</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 p-2 rounded-lg mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Easy Applications</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Apply for leave in seconds with automated balance checking.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 p-2 rounded-lg mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Manager Approvals</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">One-click approvals and rejections for supervisors.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10">
                        <a href="{{ route('login') }}" class="inline-block bg-blue-600 dark:bg-slate-100 text-white dark:text-slate-900 px-8 py-3 rounded-lg font-bold shadow-lg shadow-blue-200 dark:shadow-none hover:bg-blue-700 dark:hover:bg-white transition transform hover:-translate-y-1">
                            Get Started
                        </a>
                    </div>
                </div>
                <div class="lg:w-1/3 bg-blue-600 dark:bg-blue-700 p-8 lg:p-16 flex flex-col justify-center text-white">
                    <div class="mb-4 opacity-50">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-black mb-4">Plan Ahead</h2>
                    <p class="opacity-90 leading-relaxed">Check your remaining balances and pending requests from your personal dashboard anytime, anywhere.</p>
                </div>
            </main>
        </div>
        <footer class="mt-8 text-sm text-gray-500 dark:text-gray-400">
            &copy; {{ date('Y') }} ELMS. Built for efficiency.
        </footer>
    </body>
</html>
