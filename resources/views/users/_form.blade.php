@php
    $isEdit = isset($user);
@endphp

<form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}">

    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- NAME --}}
    <div class="mb-3">
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
            class="w-full border px-3 py-2 rounded" placeholder="Tên">

        @error('name')
            <small class="text-red-500">{{ $message }}</small>
        @enderror
    </div>

    {{-- EMAIL --}}
    <div class="mb-3">
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
            class="w-full border px-3 py-2 rounded" placeholder="Email">

        @error('email')
            <small class="text-red-500">{{ $message }}</small>
        @enderror
    </div>

    {{-- PASSWORD --}}
    <div class="mb-3">
        <input type="password" name="password" class="w-full border px-3 py-2 rounded"
            placeholder="{{ $isEdit ? 'Đổi mật khẩu (không bắt buộc)' : 'Mật khẩu' }}">

        @error('password')
            <small class="text-red-500">{{ $message }}</small>
        @enderror
    </div>

    {{-- CONFIRM PASSWORD --}}
    <div class="mb-3">
        <input type="password" name="password_confirmation" class="w-full border px-3 py-2 rounded"
            placeholder="Nhập lại mật khẩu">
    </div>

    {{-- ROLE --}}
    <div class="mb-4">
        <label class="block font-medium mb-2">
            Vai trò
        </label>

        <div class="space-y-2 border p-3 rounded">

            @foreach($roles as $role)

                <label class="flex items-center gap-2">

                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(
                        in_array(
                            $role->name,
                            old(
                                'roles',
                                isset($user)
                                ? $user->roles->pluck('name')->toArray()
                                : []
                            )
                        )
                    )>

                    {{ $role->name }}

                </label>

            @endforeach

        </div>

        @error('roles')
            <small class="text-red-500">{{ $message }}</small>
        @enderror
    </div>

    {{-- BUTTONS --}}
    <div class="mt-6 flex justify-center gap-3">

        <button type="submit"
            class="submitBtn bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded flex items-center gap-2">

            <span class="btnText">
                {{ $isEdit ? 'Cập nhật' : 'Tạo người dùng' }}
            </span>

            <svg class="loadingIcon hidden animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">

                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>

                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8z">
                </path>

            </svg>

        </button>

        <button type="button" onclick="closeModal()" class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded">
            Huỷ
        </button>

    </div>

</form>