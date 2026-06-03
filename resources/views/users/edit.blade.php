<x-app-layout>

    <div class="container py-4">

        <h2 class="mb-4">Sửa người dùng</h2>

        <form method="POST" action="{{ route('users.update', $user) }}">

            @csrf
            @method('PUT')

            {{-- NAME --}}
            <div class="mb-3">

                <label class="form-label">Tên</label>

                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}">

                @error('name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror

            </div>

            {{-- EMAIL --}}
            <div class="mb-3">

                <label class="form-label">Email</label>

                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">

                @error('email')
                    <div class="text-danger">{{ $message }}</div>
                @enderror

            </div>

            {{-- ROLES --}}
            <div class="card">

                <div class="card-header">
                    Vai trò
                </div>

                <div class="card-body">

                    @foreach($roles as $role)

                        <div class="form-check">

                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}"
                                id="role_{{ $role->id }}" @checked(
                                    $user->roles->contains(
                                        'name',
                                        $role->name
                                    )
                                )>

                            <label class="form-check-label" for="role_{{ $role->id }}">

                                {{ $role->name }}

                            </label>

                        </div>

                    @endforeach

                </div>

            </div>

            {{-- BUTTON --}}
            <div class="mt-3">

                <button class="btn btn-success">
                    Lưu thay đổi
                </button>

                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Quay lại
                </a>

            </div>

        </form>

    </div>

</x-app-layout>