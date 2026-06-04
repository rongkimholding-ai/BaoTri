<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

<div class="min-h-screen flex items-center justify-center px-6">

    <div class="max-w-6xl w-full bg-white rounded-3xl shadow-xl overflow-hidden">

        <div class="grid md:grid-cols-2 items-center">

            <div class="p-12">

                <span class="inline-block px-4 py-2 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
                    Maintenance System
                </span>

                <h1 class="mt-6 text-5xl font-bold text-slate-800">
                    Hệ thống quản lý bảo trì
                </h1>

                <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                    Quản lý yêu cầu bảo trì, theo dõi tiến độ xử lý,
                    phân công nhân sự và lưu trữ lịch sử bảo trì
                    trên một nền tảng tập trung.
                </p>

                <div class="mt-10 flex gap-4">

                    <a href="{{ route('login') }}"
                       class="px-6 py-3 bg-amber-600 text-white rounded-xl font-semibold hover:bg-amber-700">
                        Đăng nhập
                    </a>

                    @if(Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="px-6 py-3 border border-amber-600 text-amber-600 rounded-xl font-semibold hover:bg-amber-50">
                        Đăng ký
                    </a>
                    @endif

                </div>

            </div>

            <div class="bg-amber-50 p-8 flex justify-center">

                <img
                    src="{{ asset('images/maintenance.png') }}"
                    alt="Maintenance"
                    class="max-h-[500px]"
                >

            </div>

        </div>

    </div>

</div>

</body>
</html>