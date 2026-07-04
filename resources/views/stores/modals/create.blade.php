<div class="modal fade" id="createStoreModal" tabindex="-1" aria-labelledby="createStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('stores.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createStoreModalLabel">Thêm cửa hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @php($store = null)
                    @include('stores._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </form>
    </div>
</div>