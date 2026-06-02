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
    </head>
    <body class="bg-gray-50 text-gray-900 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col font-sans">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-2 border border-blue-600 bg-blue-600 text-white rounded-md text-sm font-bold shadow-sm hover:bg-blue-700 transition"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-2 text-gray-700 font-semibold hover:text-blue-600 transition"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-2 border border-blue-600 text-blue-600 rounded-md text-sm font-bold hover:bg-blue-50 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex items-center justify-center w-full grow">
            <main class="flex max-w-[335px] w-full flex-col lg:max-w-4xl lg:flex-row bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                <div class="flex-1 p-8 lg:p-16">
                    <div class="mb-8">
                        <span class="text-blue-600 font-black text-3xl tracking-tighter">ELMS</span>
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 mb-4 tracking-tight">Employee Leave Management System</h1>
                    <p class="text-lg text-gray-600 mb-8 leading-relaxed">Streamline your organization's leave requests, approvals, and reporting in one central place.</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Easy Applications</h3>
                                <p class="text-gray-600 text-sm">Apply for leave in seconds with automated balance checking.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Manager Approvals</h3>
                                <p class="text-gray-600 text-sm">One-click approvals and rejections for supervisors.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10">
                        <a href="{{ route('login') }}" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition transform hover:-translate-y-1">
                            Get Started
                        </a>
                    </div>
                </div>
                <div class="lg:w-1/3 bg-blue-600 p-8 lg:p-16 flex flex-col justify-center text-white">
                    <div class="mb-4 opacity-50">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-black mb-4">Plan Ahead</h2>
                    <p class="opacity-90 leading-relaxed">Check your remaining balances and pending requests from your personal dashboard anytime, anywhere.</p>
                </div>
            </main>
        </div>
        <footer class="mt-8 text-sm text-gray-500">
            &copy; {{ date('Y') }} ELMS. Built for efficiency.
        </footer>
    </body>
</html>
