@php
    $module = session('current_module');
@endphp
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:ml-10 gap-8">
                    @if($module === 'facility')
                        {{-- Danh sách bảo trì --}}
                        @can('view data')
                            <a href="{{ route('maintenance-requests.index') }}"
                                class="nav-item {{ request()->routeIs('maintenance-requests.*') ? 'active' : '' }}">
                                Bảo trì cơ sở
                            </a>
                        @endcan
                        @can('view report')
                            {{-- Báo cáo --}}
                            <a href="{{ route('reports.technicians') }}"
                                class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                Báo cáo Công việc
                            </a>
                        @endcan
                    @endif
                    @if($module === 'system')

                        {{-- Danh sách CV hạ tầng --}}
                        @can('view-system-task')
                            <a href="{{ route('maintenance-system.index') }}"
                                class="nav-item {{ request()->routeIs('maintenance-system.*') ? 'active' : '' }}">
                                Bảo trì hạ tầng
                            </a>
                        @endcan
                    @endif

                    @role('admin')
                    <a href="{{ route('stores.index') }}"
                                class="nav-item {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                                Cửa hàng
                            </a>
                    {{-- Ngày lễ --}}
                    <a href="{{ route('holiday-calendars.index') }}"
                        class="nav-item {{ request()->routeIs('holiday-calendars.*') ? 'active' : '' }}">
                        Ngày lễ
                    </a>
                    {{-- Hệ thống --}}
                    <div class="relative h-16 flex items-center" x-data="{ openSystem: false }">

                        <button type="button" @click="openSystem = !openSystem"
                            class="nav-item {{ request()->routeIs('users.*', 'roles.*', 'permissions.*') ? 'active' : '' }}">
                            Hệ thống &nbsp;

                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSystem }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openSystem" @click.outside="openSystem = false" x-transition
                            class="absolute left-0 top-16 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden"
                            style="display:none;">
                            <a href="{{ route('users.index') }}"
                                class="dropdown-item {{ request()->routeIs('users.*') ? 'dropdown-active' : '' }}">
                                Người dùng
                            </a>

                            <a href="{{ route('roles.index') }}"
                                class="dropdown-item {{ request()->routeIs('roles.*') ? 'dropdown-active' : '' }}">
                                Vai trò
                            </a>

                            <a href="{{ route('permissions.index') }}"
                                class="dropdown-item {{ request()->routeIs('permissions.*') ? 'dropdown-active' : '' }}">
                                Quyền
                            </a>
                        </div>

                    </div>

                    @endrole

                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Hồ sơ') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                this.closest('form').submit();">
                                {{ __('Đăng xuất') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('maintenance-requests.index')"
                :active="request()->routeIs('maintenance-requests.index')">
                {{ __('Maintenance Requests') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Hồ sơ') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                        this.closest('form').submit();">
                        {{ __('Đăng xuất') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
    .nav-item {
        display: flex;
        align-items: center;
        height: 64px;
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: all .15s ease;
    }

    .nav-item:hover {
        color: #374151;
        border-bottom-color: #d1d5db;
    }

    .nav-item.active {
        color: #111827;
        border-bottom-color: #818cf8;
    }

    .dropdown-item {
        display: block;
        padding: 12px 16px;
        font-size: 14px;
        color: #374151;
        transition: background .15s ease;
    }

    .dropdown-item:hover {
        background: #f3f4f6;
    }

    .dropdown-active {
        background: #eef2ff;
        color: #4338ca;
        font-weight: 600;
    }
</style>