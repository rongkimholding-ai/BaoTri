<x-app-layout>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-4">

                <h2 class="text-xl font-semibold">Users</h2>

                <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded">
                    + Thêm user
                </button>

            </div>

            {{-- TABLE --}}
            <div class="bg-white shadow-sm rounded-lg p-6">

                <table class="w-full">

                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Tên</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th width="120"></th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($users as $u)

                            <tr class="border-b">

                                <td class="py-2">{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>

                                <td>
                                    {{ $u->getRoleNames()->join(', ') }}
                                </td>

                                <td class="py-2">
                                    <button onclick="openEditModal({{ $u->id }})" class="text-blue-600">
                                        Sửa
                                    </button>
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
    <div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center
            bg-black/50 opacity-0 pointer-events-none
            transition-opacity duration-200">

        <div id="userModalBox" class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6
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

    <script>

        function openModal() {

            const modal = document.getElementById('userModal');
            const box = document.getElementById('userModalBox');

            modal.classList.remove('pointer-events-none');
            modal.classList.add('opacity-100');

            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);

        }

        function closeModal() {

            const modal = document.getElementById('userModal');
            const box = document.getElementById('userModalBox');

            box.classList.add('scale-95', 'opacity-0');
            modal.classList.remove('opacity-100');

            modal.classList.add('pointer-events-none');

        }

        function openCreateModal() {

            fetch('/users/create')
                .then(r => r.text())
                .then(html => {

                    document.getElementById('modalTitle').innerText = 'Thêm user';
                    document.getElementById('modalContent').innerHTML = html;

                    openModal();

                });

        }

        function openEditModal(id) {

            fetch(`/users/${id}/edit`)
                .then(r => r.text())
                .then(html => {

                    document.getElementById('modalTitle').innerText = 'Sửa user';
                    document.getElementById('modalContent').innerHTML = html;

                    openModal();

                });

        }

        document.getElementById('userModal')
            .addEventListener('click', function (e) {

                if (e.target === this) closeModal();

            });

        document.addEventListener('keydown', function (e) {

            if (e.key === 'Escape') closeModal();

        });

    </script>

</x-app-layout>