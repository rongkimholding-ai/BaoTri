<div class="modal fade" id="editModal">

<div class="modal-dialog modal-lg">

<form id="editForm" method="POST">

    @csrf
    @method('PUT')

    <div class="modal-content">

        <div class="modal-header">

            <h5>Cập nhật</h5>

        </div>

        <div class="modal-body">

            @include('maintenance._form')

        </div>

        <div class="modal-footer">

            <button class="btn btn-primary">

                Cập nhật

            </button>

        </div>

    </div>

</form>

</div>

</div>