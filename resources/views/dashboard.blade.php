<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-center text-gray-800 leading-tight">
            CHÀO MỪNG ĐẾN VỚI TRANG QUẢN LÝ CÔNG VIỆC HỖ TRỢ CỦA BẢO TRÌ VÀ HẠ TẦNG.
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-xl p-8 flex flex-col items-center">
                <div class="flex flex-row gap-8 w-full justify-center items-center">
                    <a href="{{ route('maintenance-requests.index') }}"
                       class="w-64 flex items-center justify-center gap-3 bg-blue-500 hover:bg-blue-600 text-white px-6 py-4 rounded-lg transition-colors duration-150 text-lg font-semibold shadow text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4 4 4-4m0-5V3m-8 14V3" />
                        </svg>
                        Danh sách Bảo trì
                    </a>
                    <!-- <a href="{{ route('maintenance-system.index') }}"
                       class="w-64 flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white px-6 py-4 rounded-lg transition-colors duration-150 text-lg font-semibold shadow text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L16 12.25V19M19 13V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h7" />
                        </svg>
                        Danh sách CV hạ tầng
                    </a> -->
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
