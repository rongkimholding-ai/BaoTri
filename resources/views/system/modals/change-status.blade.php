<form method="POST" action="{{ route('maintenance-system.change-status.update', $maintenanceSystem) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Trạng thái</label>
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <input type="hidden" name="accuracy" id="accuracy">
        <div class="alert alert-info">
            {{ config('sla_status.names_ht')[$maintenanceSystem->status] }} →
            {{ config('sla_status.names_ht')[$status] }}
        </div>
    </div>

    <div class="mb-3 d-none" id="delayReasonGroup">
        <label>Lý do trễ</label>
        <textarea class="form-control" rows="3" name="delay_reason"></textarea>
    </div>

    <div class="mb-3 d-none" id="workType">
        <label>Loại công việc</label>
        <select class="form-control" name="work_type">
            <option value="">-- Chọn loại công việc --</option>
            @php
                $workTypeNames = config('work_type.name');
                $selectedWorkType = old('work_type', $maintenanceSystem->work_type ?? '');
            @endphp
            @foreach($workTypeNames as $code => $name)
                <option value="{{ $code }}" @if($selectedWorkType == $code) selected @endif>{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3 d-none" id="imageSystemUploadWrapper">
        <label for="completionSystemImages" class="form-label">Ảnh hoàn thành</label>
        <input type="file" id="completionSystemImages" class="form-control" multiple accept="image/*">
        <small class="text-muted">Có thể chọn nhiều ảnh</small>
    </div>

    <div class="mb-3">
        <label>Ghi chú</label>
        <textarea class="form-control" rows="3" name="note"></textarea>
    </div>

    <div class="mt-6 flex justify-end gap-2 border-t pt-4">
        <!-- <button type="button" onclick="closeMaintenanceModal()" class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">Hủy</button> -->
        <button type="submit" id="confirmSystemChangeStatus" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Lưu</button>
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