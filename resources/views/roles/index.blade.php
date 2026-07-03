<x-app-layout>
    <div class="py-6">
        <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Danh sách các Vai trò</h2>
                <button onclick="openRoleModal('create')" class="bg-blue-500 text-white px-4 py-2 rounded">
                    + Thêm Vai trò
                </button>
            </div>
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
            <div class="mb-3">{{ $roles->links() }}</div>
            {{-- TABLE --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <table class="w-full">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Tên vai trò</th>
                            <th>Các quyền</th>
                            <th width="120"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr class="border-b">
                                <td class="py-2">{{ $role->name }}</td>
                                <td class="text-sm text-gray-600" style="max-width:400px;">
                                    @php
                                        $permissions = $role->permissions;
                                    @endphp
                                    @foreach($permissions as $permission)
                                        <div class="inline-block mr-1 mb-1 py-1">
                                            <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2 py-1 rounded border border-gray-300">
                                                {{ $permission->name }}
                                            </span>
                                        </div>
                                    @endforeach
                               
                                </td>
                                <td class="py-2">
                                    <button onclick="openRoleModal('edit', {{ $role->id }})" class="btn btn-outline-primary">
                                        Sửa
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $roles->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div id="roleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 opacity-0 pointer-events-none transition-opacity duration-200">
        <div id="roleModalBox" class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6 transform scale-95 opacity-0 transition-all duration-200">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-lg font-semibold"></h2>
                <button onclick="closeRoleModal()" class="text-gray-500 hover:text-red-500 text-xl">✕</button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        const modal = document.getElementById('roleModal');
        const box = document.getElementById('roleModalBox');
        let currentAction = null;

        function openRoleModal(action, id = null) {
            currentAction = action;
            let url = action === 'create' ? '/roles/create' : `/roles/${id}/edit`;
            let title = action === 'create' ? 'Thêm vai trò' : 'Sửa vai trò';
            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('modalTitle').innerText = title;
                    document.getElementById('modalContent').innerHTML = html;
                    showRoleModal();
                });
        }

        function showRoleModal() {
            modal.classList.remove('pointer-events-none');
            modal.classList.add('opacity-100');
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeRoleModal() {
            box.classList.add('scale-95', 'opacity-0');
            modal.classList.remove('opacity-100');
            modal.classList.add('pointer-events-none');
        }

        // click outside modal box to close
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeRoleModal();
            }
        });
        // ESC close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeRoleModal();
            }
        });
    </script>
</x-app-layout>