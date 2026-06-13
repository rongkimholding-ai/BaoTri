<x-app-layout :title="$title">

    <x-slot name="header">
        <h2 class="h4 mb-0">
            {{ $title }}
        </h2>
    </x-slot>

    <div class="card">
        <div class="card-body">

            <form
                action="{{ route('holiday-calendars.store') }}"
                method="POST"
            >
                @csrf

                @include('holiday-calendars._form')

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu
                </button>

                <a
                    href="{{ route('holiday-calendars.index') }}"
                    class="btn btn-secondary"
                >
                    Quay lại
                </a>

            </form>

        </div>
    </div>

</x-app-layout>