<div class="modal fade" id="createModal">

    <div class="modal-dialog modal-xl">

        <form id="createForm" action="{{ route('maintenance-requests.store') }}" method="POST">

            @csrf

            <div class="modal-content">

                <div class="modal-header">

                    <h5>Thêm mới</h5>

                </div>
                <div id="create-form-errors"
                    class="alert alert-danger d-none">
                </div>
                <div class="modal-body">

                    @include('maintenance._form')

                </div>

                <div class="modal-footer">

                    <button class="btn btn-primary">

                        Lưu

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>