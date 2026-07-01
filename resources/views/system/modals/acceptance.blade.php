<div class="modal fade" id="acceptanceSystemModal" tabindex="-1" aria-labelledby="acceptanceSystemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="acceptanceSystemModalLabel">Nghiệm thu công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="acceptance_system_id">
                <div class="mb-3">
                    <label for="acceptance_result" class="form-label">Kết quả nghiệm thu</label>
                    <select class="form-select" id="acceptance_result" required>
                        <option value="">Chọn kết quả</option>
                        <option value="accepted">Đạt</option>
                        <option value="rejected">Không đạt</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="acceptance_note" class="form-label">Ghi chú / Lý do</label>
                    <textarea class="form-control" id="acceptance_note" rows="4" placeholder="Nhập ghi chú hoặc lý do"></textarea>
                </div>
                <div id="acceptance-error" class="alert alert-danger d-none"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="submitAcceptanceSystem">Xác nhận</button>
            </div>
        </div>
    </div>
</div>