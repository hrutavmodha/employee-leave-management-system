<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
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
                <div class="hidden font-black space-x-8 sm:-my-px sm:ms:10 sm:flex">
                    <x-nav-link class="text-3xl" :href="route('dashboard')" :active="request()->routeIs('dashboard')">
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

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 gap-2">
                            @if(Auth::user()->profile_picture)
                                <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="w-6 h-6 rounded-full object-cover border border-gray-300">
                            @else
                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-xs border border-gray-300">
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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-400"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="sm:hidden absolute w-full left-0 bg-white border-b border-gray-100 shadow-xl z-50">
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
            <div class="border-t border-gray-200 my-2"></div>
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
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 flex items-center gap-3">
                <div class="shrink-0">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-300">
                    @else
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-sm border border-gray-300">
                            {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div>
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">{{ Auth::user()->role }}</div>
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
