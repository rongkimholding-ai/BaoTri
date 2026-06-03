@php
    $isEdit = isset($role);
@endphp

<form method="POST"
      action="{{ $isEdit ? route('roles.update',$role) : route('roles.store') }}">

    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    <!-- NAME -->
    <div class="mb-4">

        <label class="block font-medium mb-1">
            Tên role
        </label>

        <input type="text"
               name="name"
               value="{{ old('name', $role->name ?? '') }}"
               class="w-full border rounded px-3 py-2">

        @error('name')
            <div class="text-red-500 text-sm mt-1">
                {{ $message }}
            </div>
        @enderror

    </div>

    <!-- PERMISSIONS -->
    <div class="mb-4">

        <label class="block font-medium mb-2">
            Permissions
        </label>

        <div class="grid grid-cols-3 gap-2 border p-3 rounded max-h-96 overflow-y-auto">

            @foreach($permissions as $p)

                <label class="flex items-center gap-2 text-sm">

                    <input type="checkbox"
                           name="permissions[]"
                           value="{{ $p->name }}"

                           @checked(
                               isset($role) &&
                               $role->permissions->contains('name',$p->name)
                           )>

                    {{ $p->name }}

                </label>

            @endforeach

        </div>

    </div>

    <!-- ACTION -->
    <div class="mt-6 flex justify-center gap-3">

        <button class="bg-green-500 text-white px-4 py-2 rounded">
            {{ $isEdit ? 'Cập nhật' : 'Lưu' }}
        </button>

        <button type="button"
                onclick="closeModal()"
                class="bg-gray-400 text-white px-4 py-2 rounded">
            Huỷ
        </button>

    </div>

</form>