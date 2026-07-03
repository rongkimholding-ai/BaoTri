<div class="modal fade" id="editStoreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">

        <form id="editStoreForm" method="POST">

            @csrf
            @method('PUT')

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Cập nhật cửa hàng
                    </h5>

                    <button class="btn-close"
                            type="button"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    @php($store = null)

                    @include('stores._form')

                </div>

                <div class="modal-footer">

                    <button class="btn btn-secondary"
                            data-bs-dismiss="modal"
                            type="button">
                        Đóng
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Lưu
                    </button>

                </div>

            </div>

        </form>

    </div>
</div>