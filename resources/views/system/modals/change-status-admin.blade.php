<div class="modal fade" id="changeStatusSystemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thay đổi trạng thái hệ thống</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusRequestId" name="id">
                <input type="hidden" id="newStatus" name="status">

                <div class="mb-3">
                    <label for="statusLabel" class="form-label">Trạng thái mới</label>
                    <input type="text" id="statusLabel" class="form-control" name="status_label" readonly>
                </div>

                <div class="mb-3 d-none" id="statusSelectWrapper">
                    <label for="statusSelect" class="form-label">Chọn trạng thái</label>
                    <select id="statusSelect" class="form-select" name="status_select"></select>
                </div>

                <div class="mb-3 d-none" id="delayReasonGroup">
                    <label for="statusDelayReason" class="form-label">Lý do trễ</label>
                    <textarea id="statusDelayReason" name="delay_reason" rows="3" class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label for="statusNote" class="form-label">Ghi chú</label>
                    <textarea id="statusNote" name="note" rows="4" class="form-control"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" id="confirmChangeStatusSystem" class="btn btn-primary">Xác nhận</button>
            </div>
        </div>
    </div>
</div>