@php
    $techList = collect(config('technician', []));
@endphp
<div class="modal fade" id="updateTechMaintenanceModal" tabindex="-1" aria-labelledby="updateTechModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="updateTechMaintenanceForm" method="POST" action="" autocomplete="off">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateTechModalLabel">Cập nhật kỹ thuật viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="technician_name" class="form-label">Tên kỹ thuật viên <span class="text-danger">*</span></label>
                        <select class="form-select" id="technician_name" name="technician_name" required>
                            <option value="">-- Chọn kỹ thuật viên --</option>
                            @foreach($techList as $tech)
                                <option 
                                    value="{{ $tech['name'] }}"
                                    data-email="{{ $tech['email'] ?? '' }}"
                                    data-mobile="{{ $tech['mobile'] ?? '' }}"
                                >{{ $tech['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="technician_email" class="form-label">Email kỹ thuật viên <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="technician_email" name="technician_email" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="technician_mobile" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="technician_mobile" name="technician_mobile" maxlength="30">
                    </div>
                    <div class="alert alert-danger d-none" id="updateTechError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- JS đã xử lý trong resources/js/maintenance.js (xem từ dòng 1576) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const technicianNameSelect = document.getElementById('technician_name');
    const technicianEmailInput = document.getElementById('technician_email');
    const technicianMobileInput = document.getElementById('technician_mobile');

    // Auto-fill email & mobile from option data-*
    technicianNameSelect?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected && selected.value) {
            const email = selected.getAttribute('data-email') || '';
            const mobile = selected.getAttribute('data-mobile') || '';
            technicianEmailInput.value = email;
            technicianMobileInput.value = mobile;
        } else {
            technicianEmailInput.value = '';
            technicianMobileInput.value = '';
        }
    });
});
</script>