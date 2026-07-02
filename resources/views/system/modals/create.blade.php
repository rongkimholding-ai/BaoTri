<form action="{{ route('maintenance-system.store') }}" method="POST" id="maintenanceSystemCreateForm">
    @csrf
    @include('system.partials._form')
    <div class="mt-6 flex justify-end gap-2 border-t pt-4">
        <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button> -->
        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Lưu</button>
    </div>
</form>