<x-app-layout :title="$title">
    <x-slot name="header">
        <h2 class="h4 mb-0">{{ $title }}</h2>
    </x-slot>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('holiday-calendars.update', $holidayCalendar) }}" method="POST">
                @csrf
                @method('PUT')
                @include('holiday-calendars._form')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="{{ route('holiday-calendars.index') }}" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>