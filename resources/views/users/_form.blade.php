@php $isEdit = isset($user); @endphp

<form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- NAME --}}
    <div class="mb-4">
        <label for="user_name" class="block font-medium mb-1">Tên</label>
        <input id="user_name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
               class="w-full border rounded px-3 py-2" required autofocus>
        @error('name')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- EMAIL --}}
    <div class="mb-4">
        <label for="user_email" class="block font-medium mb-1">Email</label>
        <input id="user_email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
               class="w-full border rounded px-3 py-2" required>
        @error('email')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- PASSWORD --}}
    <div class="mb-4">
        <label for="user_password" class="block font-medium mb-1">
            {{ $isEdit ? 'Đổi mật khẩu (không bắt buộc)' : 'Mật khẩu' }}
        </label>
        <input id="user_password" type="password" name="password"
               class="w-full border rounded px-3 py-2"
               @if(!$isEdit) required @endif>
        @error('password')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- CONFIRM PASSWORD --}}
    <div class="mb-4">
        <label for="user_password_confirmation" class="block font-medium mb-1">Nhập lại mật khẩu</label>
        <input id="user_password_confirmation" type="password" name="password_confirmation"
               class="w-full border rounded px-3 py-2"
               @if(!$isEdit) required @endif>
    </div>

    {{-- ROLE --}}
    <div class="mb-4">
        <label class="block font-medium mb-2">Vai trò</label>
        <div class="grid grid-cols-3 gap-2 border p-3 rounded max-h-64 overflow-y-auto">
            @foreach($roles as $role)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                        @checked(in_array(
                            $role->name,
                            old('roles',
                                isset($user)
                                ? $user->roles->pluck('name')->toArray()
                                : []
                            )
                        ))>
                    {{ $role->name }}
                </label>
            @endforeach
        </div>
        @error('roles')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- BUTTONS --}}
    <div class="mt-6 flex justify-center gap-3">
        <button type="submit"
            class="submitBtn bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded flex items-center gap-2">
            <span class="btnText">{{ $isEdit ? 'Cập nhật' : 'Tạo người dùng' }}</span>
            <svg class="loadingIcon hidden animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8z"></path>
            </svg>
        </button>
        <button type="button" onclick="closeUserModal()"
            class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded">
            Huỷ
        </button>
    </div>
</form>