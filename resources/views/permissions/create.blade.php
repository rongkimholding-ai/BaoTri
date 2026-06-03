<x-app-layout>

<div class="container">

    <h2>Thêm Permission</h2>

    <form
        action="{{ route('permissions.store') }}"
        method="POST"
    >

        @csrf

        <div class="mb-3">

            <label>Tên quyền</label>

            <input
                type="text"
                name="name"
                class="form-control"
            >

        </div>

        <button
            class="btn btn-success"
        >
            Lưu
        </button>

    </form>

</div>

</x-app-layout>