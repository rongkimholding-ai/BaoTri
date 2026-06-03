<x-app-layout>

<div class="container">

    <div class="d-flex justify-content-between mb-3">

        <h2>Permissions</h2>

        <a
            href="{{ route('permissions.create') }}"
            class="btn btn-primary"
        >
            Thêm mới
        </a>

    </div>

    <table class="table table-bordered">

        <thead>
            <tr>
                <th>ID</th>
                <th>Tên quyền</th>
                <th></th>
            </tr>
        </thead>

        <tbody>

        @foreach($permissions as $permission)

            <tr>

                <td>{{ $permission->id }}</td>

                <td>{{ $permission->name }}</td>

                <td>

                    <a
                        href="{{ route(
                            'permissions.edit',
                            $permission
                        ) }}"
                        class="btn btn-warning btn-sm"
                    >
                        Sửa
                    </a>

                    <form
                        action="{{ route(
                            'permissions.destroy',
                            $permission
                        ) }}"
                        method="POST"
                        class="d-inline"
                    >

                        @csrf
                        @method('DELETE')

                        <button
                            class="btn btn-danger btn-sm"
                        >
                            Xóa
                        </button>

                    </form>

                </td>

            </tr>

        @endforeach

        </tbody>

    </table>

</div>

</x-app-layout>