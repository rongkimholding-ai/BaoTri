<x-app-layout>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-4">

                <h2 class="text-xl font-semibold">Permissions</h2>

                <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded">
                    + Thêm permission
                </button>

            </div>

            {{-- TABLE --}}
            <div class="bg-white shadow-sm rounded-lg p-6">

                <table class="w-full">

                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Tên permission</th>
                            <th width="120"></th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($permissions as $p)

                            <tr class="border-b">

                                <td class="py-2">
                                    {{ $p->name }}
                                </td>

                                <td class="py-2 flex gap-3">

                                    <button onclick="openEditModal({{ $p->id }})" class="text-blue-600">
                                        Sửa
                                    </button>

                                    <form method="POST" action="{{ route('permissions.destroy', $p) }}"
                                        onsubmit="return confirm('Xóa permission?')">

                                        @csrf
                                        @method('DELETE')

                                        <button class="text-red-600">
                                            Xóa
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                {{ $permissions->links() }}
            </div>
        </div>

    </div>

    {{-- MODAL --}}
    <div id="permissionModal" class="fixed inset-0 z-50 flex items-center justify-center
            bg-black/50 opacity-0 pointer-events-none
            transition-opacity duration-200">

        <div id="permissionModalBox" class="bg-white w-full max-w-2xl rounded-lg shadow-lg p-6
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

            const modal = document.getElementById('permissionModal');
            const box = document.getElementById('permissionModalBox');

            modal.classList.remove('pointer-events-none');
            modal.classList.add('opacity-100');

            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);

        }

        function closeModal() {

            const modal = document.getElementById('permissionModal');
            const box = document.getElementById('permissionModalBox');

            box.classList.add('scale-95', 'opacity-0');
            modal.classList.remove('opacity-100');

            modal.classList.add('pointer-events-none');

        }

        function openCreateModal() {

            fetch('/permissions/create')
                .then(r => r.text())
                .then(html => {

                    document.getElementById('modalTitle').innerText = 'Thêm permission';
                    document.getElementById('modalContent').innerHTML = html;

                    openModal();

                });

        }

        function openEditModal(id) {

            fetch(`/permissions/${id}/edit`)
                .then(r => r.text())
                .then(html => {

                    document.getElementById('modalTitle').innerText = 'Sửa permission';
                    document.getElementById('modalContent').innerHTML = html;

                    openModal();

                });

        }

        // click outside
        document.getElementById('permissionModal')
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