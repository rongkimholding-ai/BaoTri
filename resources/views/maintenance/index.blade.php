@php
    $title = 'Danh sách công việc bảo trì';
    $realTimeList = json_decode(file_get_contents(resource_path('json/real_time.json')), true);
    $realTimeMap = collect($realTimeList)->keyBy('key');
@endphp
<x-app-layout :title="$title">
    <x-slot name="header">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ $title }}</h3>

        <div class="d-flex gap-2">
            @can('export excel')
                <a href="{{ route('maintenance-requests.export') }}"
                class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i>
                    Xuất báo cáo
                </a>
            @endcan

            @can('create data')
                <button
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createModal">
                    <i class="bi bi-plus-circle"></i>
                    Thêm mới
                </button>
            @endcan
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('maintenance-requests.index') }}">
                <div class="row g-2">

                    <div class="col-md-2">
                        <input
                            type="text"
                            name="branch_code"
                            class="form-control"
                            placeholder="Mã cơ sở"
                            value="{{ request('branch_code') }}">
                    </div>

                    <div class="col-md-3">
                        <select class="form-control select2-branch" name="branch_name"
                                data-field="branch_name">
                            <option value="">-- Chọn cơ sở --</option>

                            @foreach($stores as $store)
                                <option value="{{ $store['name'] }}"
                                        data-code="{{ $store['code'] }}"
                                        @selected(request('branch_name') == $store['name'])>
                                    {{ $store['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="severity" class="form-control select2-branch">
                            <option value="">Loại sự cố</option>

                            @foreach($severities as $type)
                                <option
                                    value="{{ $type['key'] }}"
                                    @selected(request('severity') == $type['key'])>
                                    {{ $type['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-control select2-branch">
                            <option value="">Trạng thái</option>

                            @foreach(config('sla_status.names') as $key => $status)
                                <option
                                    value="{{ $key }}"
                                    @selected(request('status') == $key)>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            Tìm kiếm
                        </button>

                        <a href="{{ route('maintenance-requests.index') }}"
                        class="btn btn-outline-secondary">
                            Xóa
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>
    <!-- <div class="card mb-3">
        <div class="card-body">
        <form method="GET"
            action="{{ route('maintenance.export-fromto') }}"
            class="d-inline">

            <input type="date"
                name="from_date"
                value="{{ request('from_date') }}">

            <input type="date"
                name="to_date"
                value="{{ request('to_date') }}">

            <button type="submit"
                    class="btn btn-success">
                Xuất Excel
            </button>
        </form>
        </div>
    </div> -->
    </x-slot>
    <ul class="nav nav-tabs mb-3" id="requestTabs">
        <li class="nav-item">
            <button class="nav-link active"
                    data-bs-toggle="tab"
                    data-bs-target="#tab-all">
                Tất cả
            </button>
        </li>

        <li class="nav-item">
            <button class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#tab-processing">
                Đang xử lý
            </button>
        </li>

        <li class="nav-item">
            <button class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#tab-completed">
                Hoàn thành
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active"
            id="tab-all">
            @include('maintenance.partials.new_table', [
                'requests' => $allRequests,
                'stores' => $stores,
                'checks' => $checks,
                'techs' => $techs,
                'severities' => $severities,
                'realTimeMap' => $realTimeMap
            ])
        </div>

        <div class="tab-pane fade"
            id="tab-processing">
            @include('maintenance.partials.new_table', [
                'requests' => $processingRequests,
                'stores' => $stores,
                'checks' => $checks,
                'techs' => $techs,
                'severities' => $severities,
                'realTimeMap' => $realTimeMap
            ])
        </div>

        <div class="tab-pane fade"
            id="tab-completed">
            @include('maintenance.partials.new_table', [
                'requests' => $completedRequests,
                'stores' => $stores,
                'checks' => $checks,
                'techs' => $techs,
                'severities' => $severities,
                'realTimeMap' => $realTimeMap
            ])
        </div>

    </div>
    @include('maintenance.modals.create')
    @include('maintenance.modals.change_status')
    @include('maintenance.modals.log')
    @include('maintenance.modals.acceptance')
</x-app-layout>