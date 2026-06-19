<x-app-layout :title="$title">

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>
                <h4 class="mb-0">
                    Chi tiết yêu cầu #{{ $maintenanceRequest->id }}
                </h4>

                <small class="text-muted">
                    {{ $maintenanceRequest->branch_code }}
                    -
                    {{ $maintenanceRequest->branch_name }}
                </small>
            </div>

            <a
                href="{{ url()->previous() }}"
                class="btn btn-secondary"
            >
                Quay lại
            </a>

        </div>

        @include('maintenance.partials.detail')

    </div>

</x-app-layout>