<x-app-layout>

<div class="container">

    <h2>Quản lý người dùng</h2>

    <table class="table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Role</th>
                <th></th>
            </tr>
        </thead>

        <tbody>

        @foreach($users as $user)

            <tr>

                <td>{{ $user->id }}</td>

                <td>{{ $user->name }}</td>

                <td>{{ $user->email }}</td>

                <td>
                    {{ $user->roles->pluck('name')->join(', ') }}
                </td>

                <td>

                    <a
                        href="{{ route('users.edit',$user) }}"
                        class="btn btn-primary"
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