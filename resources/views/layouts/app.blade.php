@props([
    'title' => 'Công việc bảo trì'
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <link rel="icon" href="{{ asset('images/favicon-32x32.png') }}" type="image/png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <script>
        window.slaStatusNames = @json(config('sla_status.names'));
        window.slaStatusBadges = @json(config('sla_status.badge'));
        window.techNgoaiGio = @json(config('technician.ngoai_gio.name'));
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased">

<div class="min-h-screen flex flex-col">

    <!-- Top Navigation -->
    <header class="bg-white border-b shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <div class="flex items-center gap-3">
                    <x-nav-link :href="route('maintenance-requests.index')" class="flex items-center gap-3">
                        <div class="logo-img"><img src="{{ asset('images/Logo.png') }}"></div>
                        <!-- <span class="font-semibold text-gray-900 text-lg">
                            {{ config('app.name', 'Laravel') }}
                        </span> -->
                    </x-nav-link>
               
                </div>

                <div class="flex items-center gap-4">
                    @include('layouts.navigation')
                </div>

            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- Page Header -->
            @isset($header)
                <div class="mb-6">
                    <div class="bg-white rounded-xl shadow-sm border px-6 py-4">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <!-- Main Content Card -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                {{ $slot }}
            </div>
            <div id="global-loading" class="loading-overlay d-none">
                <div class="text-center">
                    <div class="spinner-border text-primary"
                        style="width:4rem;height:4rem;">
                    </div>

                    <div class="mt-3 fw-bold loading-message">
                        Đang xử lý...
                    </div>
                </div>
            </div>
        </div>
    </main>

</div>

</body>
</html>