<div class="modal fade"
     id="acceptanceModal"
     tabindex="-1">

    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Nghiệm thu công việc
                </h5>
            </div>

            <div class="modal-body">

                <input
                    type="hidden"
                    id="acceptance_request_id">

                <div class="mb-3">

                    <label class="form-label">
                        Kết quả nghiệm thu
                    </label>

                    <select
                        class="form-select"
                        id="acceptance_result">

                        <option value="">
                            Chọn kết quả
                        </option>

                        <option value="accepted">
                            Đạt
                        </option>

                        <option value="rejected">
                            Không đạt
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Ghi chú / Lý do
                    </label>

                    <textarea
                        class="form-control"
                        id="acceptance_note"
                        rows="4"></textarea>

                </div>

                <div
                    id="acceptance-error"
                    class="alert alert-danger d-none">
                </div>

            </div>

            <div class="modal-footer">

                <button
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Đóng
                </button>

                <button
                    class="btn btn-primary"
                    id="submitAcceptance">
                    Xác nhận
                </button>

            </div>

        </div>
    </div>
</div>