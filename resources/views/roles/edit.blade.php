<x-app-layout>

    <div class="container py-4">

        <h2 class="mb-4">Sửa vai trò</h2>

        <form method="POST" action="{{ route('roles.update', $role) }}">

            @csrf
            @method('PUT')

            {{-- ROLE NAME --}}
            <div class="mb-3">

                <label class="form-label">
                    Tên vai trò
                </label>

                <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}">

                @error('name')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            {{-- PERMISSIONS --}}
            <div class="card">

                <div class="card-header">
                    Danh sách quyền
                </div>

                <div class="card-body">

                    @foreach($permissions as $permission)

                        <div class="form-check">

                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                value="{{ $permission->name }}" id="perm_{{ $permission->id }}" {{-- CHECKED SAFE --}}
                                @checked(
                                    $role->permissions->contains(
                                        'name',
                                        $permission->name
                                    )
                                )>

                            <label class="form-check-label" for="perm_{{ $permission->id }}">

                                {{ $permission->name }}

                            </label>

                        </div>

                    @endforeach

                </div>

            </div>

            {{-- BUTTON --}}
            <div class="mt-3">

                <button type="submit" class="btn btn-success">

                    Lưu thay đổi

                </button>

                <a href="{{ route('roles.index') }}" class="btn btn-secondary">

                    Quay lại

                </a>

            </div>

        </form>

    </div>

</x-app-layout>