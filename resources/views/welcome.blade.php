<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bảo trì</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

    <div class="min-h-screen flex items-center justify-center px-4 md:px-6">

        <div class="max-w-5xl w-full bg-white rounded-3xl shadow-xl overflow-hidden">

            <div class="grid md:grid-cols-2 items-center">

                <section class="p-8 md:p-12">
                    <span class="inline-block px-4 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
                        Maintenance System
                    </span>
                    <h1 class="mt-5 md:mt-6 text-3xl md:text-5xl font-bold text-slate-800">
                        Hệ thống quản lý bảo trì
                    </h1>
                    <p class="mt-5 md:mt-6 text-base md:text-lg text-slate-600 leading-relaxed">
                        Quản lý yêu cầu bảo trì, theo dõi tiến độ xử lý,
                        phân công nhân sự và lưu trữ lịch sử bảo trì
                        trên một nền tảng tập trung.
                    </p>
                    <div class="mt-8 md:mt-10 flex flex-wrap gap-3 md:gap-4">
                        <a href="{{ route('login') }}"
                           class="px-5 py-3 md:px-6 md:py-3 bg-amber-600 text-white rounded-xl font-semibold hover:bg-amber-700 transition">
                            Đăng nhập
                        </a>
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="px-5 py-3 md:px-6 md:py-3 border border-amber-600 text-amber-600 rounded-xl font-semibold hover:bg-amber-50 transition">
                                Đăng ký
                            </a>
                        @endif
                    </div>
                </section>

                <section class="bg-amber-50 p-6 md:p-8 flex justify-center items-center">
                    <img
                        src="{{ asset('images/maintenance.png') }}"
                        alt="Maintenance"
                        class="max-h-64 md:max-h-[500px] w-auto object-contain"
                        loading="lazy"
                    >
                </section>

            </div>

        </div>

    </div>

</body>
</html>