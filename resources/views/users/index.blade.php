<x-app-layout>
    <div class="py-6">
        <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Danh sách Người dùng</h2>
                <div class="flex gap-2">
                    <button onclick="openUserModal('create')" class="bg-blue-500 text-white px-4 py-2 rounded">
                        + Thêm người dùng
                    </button>
                    <a href="{{ route('users.export-excel') }}"
                       class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 btn">
                        Xuất Excel
                    </a>
                </div>
            </div>
            <form action="{{ route('users.index') }}" method="GET" class="mb-4 flex items-center gap-2">
                <input type="text" name="name" value="{{ request('name') }}"
                       placeholder="Tìm theo tên..."
                       class="border rounded px-3 py-2 focus:ring focus:ring-blue-200" />
                <input type="text" name="email" value="{{ request('email') }}"
                       placeholder="Tìm theo email..."
                       class="border rounded px-3 py-2 focus:ring focus:ring-blue-200" />
                <button type="submit" class="bg-blue-500 text-white px-3 py-2 rounded">
                    Tìm kiếm
                </button>
                @if(request('name') || request('email'))
                    <a href="{{ route('users.index') }}"
                       class="btn ml-2 text-gray-500 btn-outline-secondary">
                        Xóa tìm kiếm
                    </a>
                @endif
            </form>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="mb-3">{{ $users->links() }}</div>

            {{-- TABLE --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <table class="w-full">
                    <thead>
                    <tr class="border-b text-left">
                        <th class="py-2">Tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th width="120"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $u)
                        <tr class="border-b">
                            <td class="py-2">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->getRoleNames()->join(', ') }}</td>
                            <td class="py-2">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Thao tác
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <button onclick="openUserModal('edit', {{ $u->id }})" class="dropdown-item">
                                                Sửa
                                            </button>
                                        </li>
                                        @hasrole('admin')
                                            <li>
                                                <form action="{{ route('users.reset-password', $u) }}" method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Bạn có chắc chắn muốn reset mật khẩu của {{ $u->name }}?')">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        Reset Pass
                                                    </button>
                                                </form>
                                            </li>
                                        @endhasrole
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 opacity-0 pointer-events-none transition-opacity duration-200">
        <div id="userModalBox" class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6 transform scale-95 opacity-0 transition-all duration-200">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-lg font-semibold"></h2>
                <button onclick="closeUserModal()" class="text-gray-500 hover:text-red-500 text-xl">✕</button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        const userModal = document.getElementById('userModal');
        const userModalBox = document.getElementById('userModalBox');

        function openUserModal(action, id = null) {
            let url = action === 'create' ? '/users/create' : `/users/${id}/edit`;
            let title = action === 'create' ? 'Thêm user' : 'Sửa user';
            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('modalTitle').innerText = title;
                    document.getElementById('modalContent').innerHTML = html;
                    showUserModal();
                });
        }

        function showUserModal() {
            userModal.classList.remove('pointer-events-none');
            userModal.classList.add('opacity-100');
            setTimeout(() => {
                userModalBox.classList.remove('scale-95', 'opacity-0');
                userModalBox.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeUserModal() {
            userModalBox.classList.add('scale-95', 'opacity-0');
            userModal.classList.remove('opacity-100');
            userModal.classList.add('pointer-events-none');
        }

        // click outside modal box to close
        userModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeUserModal();
            }
        });
        // ESC close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeUserModal();
            }
        });
    </script>
</x-app-layout>