<x-app-layout>

<div class="container">

    <h2>Sửa Permission</h2>

    <form
        action="{{ route(
            'permissions.update',
            $permission
        ) }}"
        method="POST"
    >

        @csrf
        @method('PUT')

        <input
            type="text"
            name="name"
            value="{{ $permission->name }}"
            class="form-control"
        >

        <button
            class="btn btn-success mt-3"
        >
            Lưu
        </button>

    </form>

</div>

</x-app-layout>