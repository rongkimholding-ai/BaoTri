@php
    $isEdit = isset($user);
@endphp

<form method="POST"
      action="{{ $isEdit ? route('users.update',$user) : route('users.store') }}">

    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- NAME --}}
    <input type="text"
           name="name"
           value="{{ old('name', $user->name ?? '') }}"
           class="w-full border mb-3 px-3 py-2 rounded"
           placeholder="Tên">

    {{-- EMAIL --}}
    <input type="email"
           name="email"
           value="{{ old('email', $user->email ?? '') }}"
           class="w-full border mb-3 px-3 py-2 rounded"
           placeholder="Email">

    {{-- ROLE RADIO --}}
    <div class="mb-4">

        <label class="block font-medium mb-2">
            Role
        </label>

        <div class="space-y-2 border p-3 rounded">

            @foreach($roles as $role)

                <label class="flex items-center gap-2">

                    <input type="radio"
                           name="role"
                           value="{{ $role->name }}"

                           @checked(
                               isset($user) &&
                               $user->roles->first()?->name == $role->name
                           )>

                    {{ $role->name }}

                </label>

            @endforeach

        </div>

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