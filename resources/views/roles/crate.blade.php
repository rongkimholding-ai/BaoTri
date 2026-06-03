<x-app-layout>

    <div class="container py-4">

        <h3>Thêm vai trò</h3>

        <form method="POST"
              action="{{ route('roles.store') }}">

            @csrf

            <div class="mb-3">

                <label class="form-label">
                    Tên vai trò
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name') }}"
                >

                @error('name')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <button
                type="submit"
                class="btn btn-success">
                Lưu
            </button>

            <a href="{{ route('roles.index') }}"
               class="btn btn-secondary">
                Quay lại
            </a>

        </form>

    </div>

</x-app-layout>