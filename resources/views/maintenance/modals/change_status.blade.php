<div class="modal fade" id="changeStatusModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Thay đổi trạng thái
                </h5>
            </div>

            <div class="modal-body">

                <input type="hidden" id="statusRequestId">

                <input type="hidden" id="newStatus">

                <div class="mb-3">

                    <label class="form-label">
                        Trạng thái mới
                    </label>

                    <input type="text"
                        id="statusLabel"
                        class="form-control"
                        readonly>
                </div>

                <div class="mb-3 d-none" id="statusSelectWrapper">
                    <label class="form-label">
                        Chọn trạng thái
                    </label>

                    <select
                        id="statusSelect"
                        class="form-select">
                    </select>
                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Ghi chú
                    </label>

                    <textarea id="statusNote" rows="4" class="form-control"></textarea>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Hủy
                </button>

                <button type="button" id="confirmChangeStatus" class="btn btn-primary">
                    Xác nhận
                </button>

            </div>

        </div>

    </div>

</div>