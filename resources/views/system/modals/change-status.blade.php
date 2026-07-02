<form method="POST" action="{{ route('maintenance-system.change-status.update', $maintenanceSystem) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Trạng thái</label>
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="alert alert-info">
            {{ config('sla_status.names_ht')[$maintenanceSystem->status] }} →
            {{ config('sla_status.names_ht')[$status] }}
        </div>
    </div>

    <div class="mb-3 d-none" id="delayReasonGroup">
        <label>Lý do trễ</label>
        <textarea class="form-control" rows="3" name="delay_reason"></textarea>
    </div>

    <div class="mb-3">
        <label>Ghi chú</label>
        <textarea class="form-control" rows="3" name="note"></textarea>
    </div>

    <div class="mt-6 flex justify-end gap-2 border-t pt-4">
        <!-- <button type="button" onclick="closeMaintenanceModal()" class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">Hủy</button> -->
        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Lưu</button>
    </div>
</form>

<script>
    $('#status').change(function () {
        $('#delayReasonGroup').toggleClass(
            'd-none',
            $(this).val() != 'LATED'
        );
    }).trigger('change');
</script>