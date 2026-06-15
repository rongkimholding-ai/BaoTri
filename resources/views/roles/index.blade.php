<x-app-layout>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-4">

                <h2 class="text-xl font-semibold">Roles</h2>

                <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded">
                    + Thêm role
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

            {{-- TABLE --}}
            <div class="bg-white shadow-sm rounded-lg p-6">

                <table class="w-full">

                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Tên role</th>
                            <th>Permissions</th>
                            <th width="120"></th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($roles as $role)

                            <tr class="border-b">

                                <td class="py-2">
                                    {{ $role->name }}
                                </td>

                                <td class="text-sm text-gray-600">
                                    {{ $role->permissions->pluck('name')->take(3)->join(', ') }}
                                </td>

                                <td class="py-2">

                                    <button onclick="openEditModal({{ $role->id }})" class="text-blue-600">
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
    <div id="roleModal" class="fixed inset-0 z-50 flex items-center justify-center
            bg-black/50 opacity-0 pointer-events-none
            transition-opacity duration-200">

        <div id="roleModalBox" class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6
                transform scale-95 opacity-0 transition-all duration-200">

            <div class="flex justify-between items-center mb-4">

                <h2 id="modalTitle" class="text-lg font-semibold"></h2>

                <button onclick="closeModal()" class="text-gray-500 hover:text-red-500 text-xl">
                    ✕
                </button>

            </div>

            <div id="modalContent"></div>

        </div>

    </div>

    {{-- JS --}}
    <script>

        function openModal() {

            const modal = document.getElementById('roleModal');
            const box = document.getElementById('roleModalBox');

            // bật interaction trước
            modal.classList.remove('pointer-events-none');

            // fade in backdrop
            modal.classList.add('opacity-100');

            // animate box
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);

        }

        function closeModal() {

            const modal = document.getElementById('roleModal');
            const box = document.getElementById('roleModalBox');

            // fade out box
            box.classList.add('scale-95', 'opacity-0');

            // fade out background
            modal.classList.remove('opacity-100');

            // disable click
            modal.classList.add('pointer-events-none');

        }

        function openCreateModal() {

            fetch('/roles/create')
                .then(res => res.text())
                .then(html => {

                    document.getElementById('modalTitle').innerText = 'Thêm role';
                    document.getElementById('modalContent').innerHTML = html;

                    openModal();

                });

        }

        function openEditModal(id) {

            fetch(`/roles/${id}/edit`)
                .then(res => res.text())
                .then(html => {

                    document.getElementById('modalTitle').innerText = 'Sửa role';
                    document.getElementById('modalContent').innerHTML = html;

                    openModal();

                });

        }

        // click outside
        document.getElementById('roleModal')
            .addEventListener('click', function (e) {

                if (e.target === this) {
                    closeModal();
                }

            });

        // ESC close
        document.addEventListener('keydown', function (e) {

            if (e.key === 'Escape') {
                closeModal();
            }

        });

    </script>

</x-app-layout>