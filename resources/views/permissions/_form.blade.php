@php
    $isEdit = isset($permission);
@endphp

<form method="POST"
      action="{{ $isEdit ? route('permissions.update',$permission) : route('permissions.store') }}">

    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- NAME --}}
    <div class="mb-4">

        <label class="block font-medium mb-1">
            Permission name
        </label>

        <input type="text"
               name="name"
               value="{{ old('name', $permission->name ?? '') }}"
               class="w-full border rounded px-3 py-2">

        @error('name')
            <div class="text-red-500 text-sm mt-1">
                {{ $message }}
            </div>
        @enderror

    </div>

    {{-- BUTTONS --}}
    <div class="mt-6 flex justify-center gap-3">

        <button type="submit"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded">
            {{ $isEdit ? 'Cập nhật' : 'Lưu' }}
        </button>

        <button type="button"
                onclick="closeModal()"
                class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded">
            Huỷ
        </button>

    </div>

</form>