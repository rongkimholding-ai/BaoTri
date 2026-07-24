<form action="{{ route('maintenance-system.update', $maintenanceSystem) }}" method="POST" enctype="multipart/form-data" id="maintenanceSystemEditForm">
    @csrf
    @method('PUT')
    @include('system.partials._form')
    <div class="mt-6 flex justify-end gap-2 border-t pt-4">
        <!-- <button type="button" onclick="closeMaintenanceModal()" class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">Đóng</button> -->
        <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Cập nhật</button>
    </div>
</form>