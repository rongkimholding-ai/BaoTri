@php $isEdit = isset($role); @endphp

<form method="POST" action="{{ $isEdit ? route('roles.update', $role) : route('roles.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Tên role --}}
    <div class="mb-4">
        <label for="role_name" class="block font-medium mb-1">Tên role</label>
        <input id="role_name" type="text" name="name" value="{{ old('name', $role->name ?? '') }}"
            class="w-full border rounded px-3 py-2" required autofocus>
        @error('name')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- Permissions --}}
    <div class="mb-4">
        <label class="block font-medium mb-2">Quyền</label>
        <div class="grid grid-cols-3 gap-2 border p-3 rounded max-h-96 overflow-y-auto">
            @foreach($permissions as $p)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="permissions[]" value="{{ $p->name }}"
                        @checked(isset($role) && $role->permissions->contains('name', $p->name))>
                    {{ $p->name }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- Nút thao tác --}}
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
        <button type="button" onclick="closeRoleModal()"
            class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded">
            Huỷ
        </button>
    </div>
</form>