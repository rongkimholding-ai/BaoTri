<form method="POST" action="{{ route('maintenance-system.change-status.update', $maintenanceSystem) }}">

    @csrf
    @method('PUT')

    <div class="mb-3">

        <label class="form-label">

            Trạng thái

        </label>
        <input
            type="hidden"
            name="status"
            value="{{ $status }}">
        <div class="alert alert-info">

            {{ config('sla_status.names_ht')[$maintenanceSystem->status] }}

            →

            {{ config('sla_status.names_ht')[$status] }}

        </div>

        <!-- <select class="form-control" name="status" id="status">

            @foreach(config('sla_status.names') as $key => $name)

                <option value="{{ $key }}" @selected($maintenanceSystem->status == $key)>

                    {{ $name }}

                </option>

            @endforeach

        </select> -->

    </div>

    <div class="mb-3 d-none" id="delayReasonGroup">

        <label>

            Lý do trễ

        </label>

        <textarea class="form-control" rows="3" name="delay_reason"></textarea>

    </div>

    <div class="mb-3">

        <label>

            Ghi chú

        </label>

        <textarea class="form-control" rows="3" name="note"></textarea>

    </div>

    <div class="text-end">

        <button class="btn btn-primary">

            Lưu

        </button>

    </div>

</form>

<script>

    $('#status').change(function () {

        $('#delayReasonGroup').toggleClass(

            'd-none',

            $(this).val() != 'LATED'

        );

    }).trigger('change');

</script>