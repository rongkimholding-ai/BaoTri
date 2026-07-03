<div class="modal fade" id="createStoreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('stores.store') }}" method="POST">
            @csrf

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Thêm cửa hàng</h5>

                    <button class="btn-close"
                            data-bs-dismiss="modal"
                            type="button"></button>
                </div>

                <div class="modal-body">
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