@php
    $isEdit = isset($user);
@endphp

<form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}">

    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- NAME --}}
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
        class="w-full border mb-3 px-3 py-2 rounded" placeholder="Tên">

    {{-- EMAIL --}}
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
        class="w-full border mb-3 px-3 py-2 rounded" placeholder="Email">

    {{-- ROLE RADIO --}}
    <div class="mb-4">

        <label class="block font-medium mb-2">
            Role
        </label>

        <div class="space-y-2 border p-3 rounded">

            @foreach($roles as $role)

                <label class="flex items-center gap-2">

                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(isset($user) && $user->roles->contains('name', $role->name))>

                    {{ $role->name }}

                </label>

            @endforeach

        </div>

    </div>

    {{-- BUTTONS --}}
    <div class="mt-6 flex justify-center gap-3">

        <button type="submit"
            class="submitBtn bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded flex items-center gap-2">

            <span class="btnText">{{ $isEdit ? 'Cập nhật' : 'Lưu' }}</span>

            <svg class="loadingIcon hidden animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">

                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>

                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8z"></path>

            </svg>

        </button>

        <button type="button" onclick="closeModal()" class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded">
            Huỷ
        </button>

    </div>

</form>