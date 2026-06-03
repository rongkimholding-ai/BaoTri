<x-app-layout>

    <div class="container py-4">

        <div class="d-flex justify-content-between mb-3">
            <h3>Danh sách vai trò</h3>

            <a href="{{ route('roles.create') }}"
               class="btn btn-primary">
                Thêm mới
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <table class="table table-bordered">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên vai trò</th>
                    <th>Guard</th>
                    <th>Số quyền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                @foreach($roles as $role)

                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->guard_name }}</td>
                        <td>

                            {{ $role->permissions->count() }}

                        </td>
                        <td>

                            <a
                                href="{{ route('roles.edit',$role) }}"
                                class="btn btn-warning btn-sm"
                            >
                                Sửa
                            </a>

                        </td>
                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</x-app-layout>