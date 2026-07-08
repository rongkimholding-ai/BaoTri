<x-app-layout :title="$title">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="p-2" style="width: 40%;">
                <h4 class="mb-0">Chi tiết yêu cầu #{{ $maintenanceRequest->id }}</h4>
                <small class="text-muted">
                    {{ $maintenanceRequest->branch_code }} - {{ $maintenanceRequest->branch_name }}
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end" style="width: 60%;">
       
                @can('change-maintenance-status')
                    @hasanyrole('technician|leadtech|admin')
                        @php
                            $status = $maintenanceRequest->sla_status;
                            $slaCode = config('sla_status.code');
                            $slaNames = config('sla_status.names');
                        @endphp
                        @if(in_array($status, [$slaCode['NEW'], $slaCode['REOPEN']]))
                            <a href="#" class="btn btn-outline-primary change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ $slaCode['PROCESSING'] }}">
                                {{ $status == $slaCode['NEW'] ? 'Tiếp nhận' : 'Xử lý lại' }}
                            </a>
                        @elseif(in_array($status, [$slaCode['PROCESSING'], $slaCode['CONTINUE_PROCESSING']]))
                            <a href="#" class="btn btn-outline-warning change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ $slaCode['PENDING'] }}">
                                {{ $slaNames['PENDING'] }}
                            </a>
                            <a href="#" class="btn btn-outline-warning change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ $slaCode['PENDING_CONTRACTOR'] }}">
                                {{ $slaNames['PENDING_CONTRACTOR'] }}
                            </a>
                            <a href="#" class="btn btn-outline-info change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ $slaCode['WAITING_CONFIRM'] }}">
                                Hoàn thành Y/C
                            </a>
                        @endif
                    @endhasanyrole

                    @hasanyrole('muasam|admin')
                        @if($maintenanceRequest->sla_status == config('sla_status.code.PENDING'))
                            <a href="#" class="btn btn-outline-primary change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ config('sla_status.code.CONTINUE_PROCESSING') }}">
                                Tiếp tục xử lý
                            </a>
                        @endif
                    @endhasanyrole

                    @hasanyrole('am|om|admin')
                        @if($maintenanceRequest->sla_status == config('sla_status.code.WAITING_CONFIRM'))
                            <a href="#" class="btn btn-outline-success change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ config('sla_status.code.CONFIRMED') }}">
                                Duyệt
                            </a>
                            <a href="#" class="btn btn-outline-danger change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ config('sla_status.code.REJECTED') }}">
                                Từ chối Y/C
                            </a>
                        @elseif($maintenanceRequest->sla_status == config('sla_status.code.REJECTED'))
                            <a href="#" class="btn btn-outline-danger change-status-btn"
                                data-id="{{ $maintenanceRequest->id }}"
                                data-status="{{ config('sla_status.code.REOPEN') }}">
                                Y/C xử lý lại
                            </a>
                        @endif
                    @endhasanyrole
                @endcan

                @can('confirm maintenance')
                    @if(in_array($maintenanceRequest->sla_status,[
                        config('sla_status.code.COMPLETED'),
                        config('sla_status.code.LATED')
                    ]) && empty($maintenanceRequest->acceptance_result))
                        <a href="#"
                           class="btn btn-outline-success acceptance-btn"
                           data-bs-toggle="modal"
                           data-bs-target="#acceptanceModal"
                           data-id="{{ $maintenanceRequest->id }}">
                            Nghiệm thu
                        </a>
                    @endif
                @endcan

                @can('remind maintenance')
                    @if(in_array($maintenanceRequest->sla_status, [
                        config('sla_status.code.PROCESSING'),
                        config('sla_status.code.CONTINUE_PROCESSING')
                    ]))
                        <a href="#"
                           class="btn btn-outline-warning btn-remind"
                           data-id="{{ $maintenanceRequest->id }}">
                            Gửi nhắc việc
                        </a>
                    @endif
                @endcan

                @can('change-status-admin')
                    <a href="#" class="btn btn-outline-secondary admin-change-status-btn"
                       data-id="{{ $maintenanceRequest->id }}"
                       data-current-status="{{ $maintenanceRequest->sla_status }}">
                        Đổi trạng thái
                    </a>
                @endcan

                @can('change-maintenance')
                    <a href="#" 
                        class="btn btn-outline-warning edit-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editModal"
                        data-id="{{ $maintenanceRequest->id }}">
                        Sửa
                    </a>
                @endcan

                @can('delete data')
                    @hasrole('admin')
                        @if($maintenanceRequest->sla_status == config('sla_status.code.PROCESSING'))
                            <form action="{{ route('maintenance-requests.destroy', $maintenanceRequest->id) }}"
                                  method="POST"
                                  style="display:inline-block"
                                  onsubmit="return confirm('Xóa bản ghi này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">Xóa</button>
                            </form>
                        @endif
                    @endhasrole
                @endcan

                <a href="{{ route('maintenance-requests.index') }}"
                   class="btn btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        @include('maintenance.partials.detail')
        @include('maintenance.modals.change_status')
        @include('maintenance.modals.acceptance')
        @include('maintenance.modals.edit')
    </div>
</x-app-layout>