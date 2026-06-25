@php
    $title = 'Danh sách công việc bảo trì';
    $realTimeMap = collect(config('real_time'))->keyBy('key');
    $statuses = config('sla_status.search_status');
    $fromDate = request('from_date', now()->startOfMonth()->format('Y-m-d'));
    $toDate = request('to_date', now()->endOfMonth()->format('Y-m-d'));
    $fromDateCompleted = request('from_date_completed', now()->startOfMonth()->format('Y-m-d'));
    $toDateCompleted = request('to_date_completed', now()->endOfMonth()->format('Y-m-d'));
@endphp

<x-app-layout :title="$title">
    <x-slot name="header">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h3 class="mb-0 fs-5 fs-md-3">{{ $title }}</h3>
            <div class="d-flex flex-column flex-md-row gap-2">
                @can('create data')
                    <button class="btn btn-outline-primary flex-fill mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-circle"></i> Thêm mới
                    </button>
                @endcan
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body search-card">
                <form method="GET" action="{{ route('maintenance-requests.index') }}">
                    <div class="row g-2 align-items-end">
                        @php
                            $filters = [
                                [
                                    'label' => 'Ngày yêu cầu (Từ)',
                                    'type' => 'date',
                                    'name' => 'from_date',
                                    'id' => 'fromDate',
                                    'value' => $fromDate,
                                ],
                                [
                                    'label' => 'Ngày yêu cầu (Đến)',
                                    'type' => 'date',
                                    'name' => 'to_date',
                                    'id' => 'toDate',
                                    'value' => $toDate,
                                ],
                                [
                                    'label' => 'Ngày hoàn thành (Từ)',
                                    'type' => 'date',
                                    'name' => 'from_date_completed',
                                    'id' => 'fromDateCompleted',
                                    'value' => $fromDateCompleted,
                                ],
                                [
                                    'label' => 'Ngày hoàn thành (Đến)',
                                    'type' => 'date',
                                    'name' => 'to_date_completed',
                                    'id' => 'toDateCompleted',
                                    'value' => $toDateCompleted,
                                ],
                            ];
                        @endphp
                        @foreach ($filters as $filter)
                            <div class="col-md-3">
                                <label for="{{ $filter['id'] }}" class="form-label mb-1">{{ $filter['label'] }}</label>
                                <input type="{{ $filter['type'] }}" id="{{ $filter['id'] }}" name="{{ $filter['name'] }}" class="form-control"
                                    value="{{ $filter['value'] }}">
                            </div>
                        @endforeach
                        <div class="col-md-3">
                            <label for="severity" class="form-label mb-1">Loại sự cố</label>
                            <select name="severity" id="severity" class="form-control select2-branch">
                                <option value="">-- Loại sự cố --</option>
                                @foreach($severities as $type)
                                    <option value="{{ $type['key'] }}" @selected(request('severity') == $type['key'])>
                                        {{ $type['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label mb-1">Trạng thái</label>
                            <select name="status" id="status" class="form-control select2-branch">
                                <option value="">-- Trạng thái --</option>
                                @foreach($statuses as $key => $status)
                                    <option value="{{ $key }}" @selected(request('status') == $key)>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="branch_name" class="form-label mb-1">Tên cơ sở</label>
                            <select class="form-control select2-branch" name="branch_name" id="branch_name" data-field="branch_name">
                                <option value="">-- Chọn cơ sở --</option>
                                @foreach(['mien_bac' => 'Miền Bắc', 'mien_nam' => 'Miền Nam'] as $region => $label)
                                    @if(!empty($stores[$region]))
                                        <optgroup label="{{ $label }}">
                                            @foreach($stores[$region] as $store)
                                                <option value="{{ $store['name'] }}" data-code="{{ $store['code'] }}"
                                                    @selected(request('branch_name') == $store['name'])>
                                                    {{ $store['name'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2 pt-md-3">
                            <button type="submit" class="btn btn-primary flex-fill mt-2 mt-md-0">Tìm kiếm</button>
                            <a href="{{ route('maintenance-requests.index') }}" class="btn btn-outline-secondary flex-fill mt-2 mt-md-0">Bỏ lọc</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-slot>

    @php
        $tabs = [
            [
                'id' => 'tab-all',
                'label' => 'Tất cả',
                'badge' => ['class' => 'bg-secondary', 'count' => $totalCount],
                'requests' => $allRequests,
            ],
            [
                'id' => 'tab-processing',
                'label' => 'Đang xử lý',
                'badge' => ['class' => 'bg-warning text-dark', 'count' => $processingCount],
                'requests' => $processingRequests,
            ],
            [
                'id' => 'tab-completed',
                'label' => 'Nghiệm thu',
                'badge' => ['class' => 'bg-success', 'count' => $completedCount],
                'requests' => $completedRequests,
            ],
        ];
    @endphp

    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" id="requestTabs">
        @foreach ($tabs as $idx => $tab)
            <li class="nav-item">
                <button class="nav-link{{ $idx === 0 ? ' active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $tab['id'] }}">
                    {{ $tab['label'] }}
                    <span class="badge {{ $tab['badge']['class'] }} ms-1">{{ number_format($tab['badge']['count']) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($tabs as $idx => $tab)
            <div class="tab-pane fade{{ $idx === 0 ? ' show active' : '' }}" id="{{ $tab['id'] }}">
                @include('maintenance.partials.new_table', [
                    'requests' => $tab['requests'],
                    'stores' => $stores,
                    'checks' => $checks,
                    'techs' => $techs,
                    'severities' => $severities,
                    'realTimeMap' => $realTimeMap
                ])
            </div>
        @endforeach
    </div>

    @include('maintenance.modals.create')
    @include('maintenance.modals.change_status')
    @include('maintenance.modals.log')
    @include('maintenance.modals.acceptance')
    @include('maintenance.modals.show')
</x-app-layout>