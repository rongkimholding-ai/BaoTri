<div class="modal fade"
     id="exportModal"
     tabindex="-1">

    <div class="modal-dialog">
        <div class="modal-content">

            <form id="exportForm"
                method="GET"
                action="{{ route('maintenance.export-fromto') }}"
                data-loading-text="Đang xuất Excel..."
                >

                <div class="modal-header">
                    <h5 class="modal-title">
                        Xuất báo cáo bảo trì
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">
                            Từ ngày
                        </label>

                        <input
                            type="date"
                            name="from_date"
                            class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Đến ngày
                        </label>

                        <input
                            type="date"
                            name="to_date"
                            class="form-control"
                            required>
                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Đóng
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">
                        Xuất Excel
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>