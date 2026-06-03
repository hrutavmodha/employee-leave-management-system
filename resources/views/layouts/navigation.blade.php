<nav x-data="{ open: false }" class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-black text-blue-600 tracking-tighter">
                        ELMS
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 lg:space-x-6 lg:-my-px lg:ms-10 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('leaves.index')" :active="request()->routeIs('leaves.*')">
                        {{ __('My Leaves') }}
                    </x-nav-link>

                    <x-nav-link :href="route('documentation')" :active="request()->routeIs('documentation')">
                        {{ __('Documentation') }}
                    </x-nav-link>

                    @if(Auth::user()->isManager() || Auth::user()->isAdmin())
                    <x-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')">
                        {{ __('Pending Approvals') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->isManager() || Auth::user()->isAdmin())
                    <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                        {{ __('Employees') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->isAdmin())
                    <x-nav-link :href="route('departments.index')" :active="request()->routeIs('departments.*')">
                        {{ __('Departments') }}
                    </x-nav-link>
                    <x-nav-link :href="route('leave-types.index')" :active="request()->routeIs('leave-types.*')">
                        {{ __('Leave Types') }}
                    </x-nav-link>
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                        {{ __('Reports') }}
                    </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings & Theme Actions -->
            <div class="flex items-center ms-4 lg:ms-6 space-x-2">
                <!-- Theme Toggle Button (Always visible on all screen sizes) -->
                <button type="button" onclick="toggleDarkMode()" class="theme-toggle-btn text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700/50 focus:outline-none rounded-lg p-2 transition duration-150 ease-in-out">
                    <svg class="theme-toggle-dark-icon w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg class="theme-toggle-light-icon hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <!-- Desktop Settings Dropdown -->
                <div class="hidden lg:flex lg:items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-black dark:text-white bg-gray-100 dark:bg-gray-800 hover:text-black dark:hover:text-white focus:outline-none transition ease-in-out duration-150 gap-2">
                                @if(Auth::user()->profile_picture)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="w-6 h-6 rounded-full object-cover border border-black/20 dark:border-white/30 shadow shadow-black/20 dark:shadow-white/10">
                                @else
                                    <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-xs border border-black/20 dark:border-white/30 shadow shadow-black/20 dark:shadow-white/10">
                                        {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Hamburger Button (Mobile Only) -->
                <div class="flex items-center lg:hidden">
                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-black dark:text-white hover:text-black dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-200 dark:focus:bg-gray-700 focus:text-black dark:focus:text-white transition duration-150 ease-in-out">
                        <div class="relative w-6 h-6">
                            <span class="hamburger-line absolute block h-0.5 w-6 bg-current transform transition-all duration-500 ease-in-out origin-center" 
                                  :class="open ? 'rotate-45 top-[11px]' : 'top-[5px]'"></span>
                            <span class="hamburger-line absolute block h-0.5 w-6 bg-current transition-all duration-500 ease-in-out" 
                                  :class="open ? 'opacity-0 top-[11px]' : 'top-[11px]'"></span>
                            <span class="hamburger-line absolute block h-0.5 w-6 bg-current transform transition-all duration-500 ease-in-out origin-center" 
                                  :class="open ? '-rotate-45 top-[11px]' : 'top-[17px]'"></span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-out duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden absolute top-full w-full left-0 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-xl z-50 max-h-[calc(100vh-4rem)] overflow-y-auto">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('leaves.index')" :active="request()->routeIs('leaves.*')">
                {{ __('My Leaves') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('documentation')" :active="request()->routeIs('documentation')">
                {{ __('Documentation') }}
            </x-responsive-nav-link>

            @if(Auth::user()->isManager() || Auth::user()->isAdmin())
            <x-responsive-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')">
                {{ __('Pending Approvals') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->isManager() || Auth::user()->isAdmin())
            <div class="border-t border-gray-300 dark:border-gray-700 my-2"></div>
            <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                {{ __('Employees') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->isAdmin())
            <x-responsive-nav-link :href="route('departments.index')" :active="request()->routeIs('departments.*')">
                {{ __('Departments') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('leave-types.index')" :active="request()->routeIs('leave-types.*')">
                {{ __('Leave Types') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                {{ __('Reports') }}
            </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-300 dark:border-gray-700">
            <div class="px-4 flex items-center">
                <div class="flex items-center gap-3">
                    <div class="shrink-0">
                        @if(Auth::user()->profile_picture)
                            <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="w-10 h-10 rounded-full object-cover border border-black/20 dark:border-white/30 shadow shadow-black/20 dark:shadow-white/10">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-sm border border-black/20 dark:border-white/30 shadow shadow-black/20 dark:shadow-white/10">
                                {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="font-medium text-base text-black dark:text-white">{{ Auth::user()->name }}</div>
                        <div class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">{{ Auth::user()->role }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                                {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
