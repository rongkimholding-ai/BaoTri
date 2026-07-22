<x-app-layout :isDashboard="true">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-center text-gray-800 leading-tight">
            CHÀO MỪNG ĐẾN VỚI TRANG QUẢN LÝ CÔNG VIỆC HỖ TRỢ CỦA BẢO TRÌ VÀ HẠ TẦNG.
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Desktop View (md+) -->
            <div class="hidden md:flex bg-white rounded-xl p-8 flex-col items-center" id="desktop-view">
                <div class="flex flex-row w-full justify-center items-center gap-32">
                    @can('view data')
                        <div class="mx-8 m-2">
                            <form method="POST" action="{{ route('select-module') }}">
                                @csrf
                                <input type="hidden" name="module" value="facility">
                                <input type="hidden" name="redirect" value="{{ route('maintenance-requests.index') }}">
                                <button type="submit"
                                    class="w-64 flex items-center justify-center gap-3 bg-gray-400 hover:bg-gray-500 text-white px-6 py-4 rounded-lg transition-colors duration-150 text-lg font-semibold shadow text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 17l4 4 4-4m0-5V3m-8 14V3" />
                                    </svg>
                                    Bảo trì xây dựng
                                </button>
                            </form>
                        </div>
                    @endcan
                    @can('view-system-task')
                        <div class="mx-8 m-2">
                            <form method="POST" action="{{ route('select-module') }}">
                                @csrf
                                <input type="hidden" name="module" value="system">
                                <input type="hidden" name="redirect" value="{{ route('maintenance-system.index') }}">
                                <button type="submit"
                                    class="w-64 flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white px-6 py-4 rounded-lg transition-colors duration-150 text-lg font-semibold shadow text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.75 17L16 12.25V19M19 13V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h7" />
                                    </svg>
                                    Bảo trì CNTT
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
           
            </div>

            <!-- Mobile View (below md) -->
            <div class="flex md:hidden bg-white rounded-xl p-6 flex-col items-center" id="mobile-view">
                <div class="flex flex-col gap-8 w-full">
                    @can('view data')
                        <form method="POST" action="{{ route('select-module') }}">
                            @csrf
                            <input type="hidden" name="module" value="facility">
                            <input type="hidden" name="redirect" value="{{ route('maintenance-requests.index') }}">
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-3 bg-gray-400 hover:bg-gray-500 text-white px-5 py-4 rounded-lg transition-colors duration-150 text-base md:text-lg font-semibold shadow text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 17l4 4 4-4m0-5V3m-8 14V3" />
                                </svg>
                                Bảo trì cơ sở
                            </button>
                        </form>
                        <!-- <a href="{{ route('maintenance-requests.index') }}"
                           class="w-64 flex items-center justify-center gap-3 bg-gray-400 hover:bg-gray-500 text-white px-6 py-4 rounded-lg transition-colors duration-150 text-lg font-semibold shadow text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4 4 4-4m0-5V3m-8 14V3" />
                            </svg>
                            Bảo trì cơ sở
                        </a> -->
                    @endcan
                    @can('view-system-task')
                        <form method="POST" action="{{ route('select-module') }}">
                            @csrf
                            <input type="hidden" name="module" value="system">
                            <input type="hidden" name="redirect" value="{{ route('maintenance-system.index') }}">
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white px-5 py-4 rounded-lg transition-colors duration-150 text-base md:text-lg font-semibold shadow text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.75 17L16 12.25V19M19 13V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h7" />
                                </svg>
                                Bảo trì hạ tầng
                            </button>
                        </form>
                        <!-- <a href="{{ route('maintenance-system.index') }}"
                                       class="w-64 flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white px-6 py-4 rounded-lg transition-colors duration-150 text-lg font-semibold shadow text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L16 12.25V19M19 13V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h7" />
                                        </svg>
                                        Bảo trì hạ tầng
                                    </a> -->
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    // Manual hack to help debug viewport (Force md classes if width >= 768px)
    document.addEventListener('DOMContentLoaded', function () {
        function checkWidth() {
            var w = window.innerWidth;
            var desktop = document.getElementById('desktop-view');
            var mobile = document.getElementById('mobile-view');
            if (w >= 768) {
                if(desktop) desktop.style.display = 'flex';
                if(mobile) mobile.style.display = 'none';
            } else {
                if(desktop) desktop.style.display = 'none';
                if(mobile) mobile.style.display = 'flex';
            }
        }
        checkWidth();
        window.addEventListener('resize', checkWidth);
    });
</script>