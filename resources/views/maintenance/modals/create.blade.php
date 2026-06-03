<div class="modal fade" id="createModal">

    <div class="modal-dialog modal-lg">

        <form id="createForm" action="{{ route('maintenance-requests.store') }}" method="POST">

            @csrf

            <div class="modal-content">

                <div class="modal-header">

                    <h5>Thêm mới</h5>

                </div>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
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