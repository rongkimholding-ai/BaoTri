<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Maintenance System') }}</title>
    <link rel="icon" href="{{ asset('images/favicon-32x32.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-100 via-white to-gray-200 flex items-center justify-center font-sans">

    <div class="w-full">

        <!-- Header -->
        <!-- <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Maintenance System
            </h1>
            <p class="text-gray-500 mt-2 text-sm">
                Hệ thống quản lý thông tin bảo trì
            </p>
        </div> -->

        <!-- Card -->
        <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8 border border-gray-100">
            {{ $slot }}
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-xs text-gray-400">
            © {{ date('Y') }} Maintenance System
        </div>

    </div>

</body>
</html>