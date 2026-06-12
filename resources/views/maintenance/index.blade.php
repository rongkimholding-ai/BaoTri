@php
    $title = 'Danh sách công việc bảo trì';
    $realTimeMap = collect(config('real_time'))->keyBy('key');
    $statuses = config('sla_status.names');
@endphp

<x-app-layout :title="$title">
    <x-slot name="header">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">{{ $title }}</h3>
            <div class="d-flex gap-2">
                @can('export excel')
                    <a href="{{ route('maintenance-requests.export') }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Xuất báo cáo
                    </a>
                @endcan
                @can('create data')
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-circle"></i> Thêm mới
                    </button>
                @endcan
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
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('maintenance-requests.index') }}">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <input type="text" name="branch_code" class="form-control" placeholder="Mã cơ sở"
                                value="{{ request('branch_code') }}">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control select2-branch" name="branch_name" data-field="branch_name">
                                <option value="">-- Chọn cơ sở --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['name'] }}" data-code="{{ $store['code'] }}"
                                        @selected(request('branch_name') == $store['name'])>
                                        {{ $store['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="severity" class="form-control select2-branch">
                                <option value="">-- Loại sự cố --</option>
                                @foreach($severities as $type)
                                    <option value="{{ $type['key'] }}" @selected(request('severity') == $type['key'])>
                                        {{ $type['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control select2-branch">
                                <option value="">-- Trạng thái --</option>
                                @foreach($statuses as $key => $status)
                                    <option value="{{ $key }}" @selected(request('status') == $key)>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Tìm kiếm</button>
                            <a href="{{ route('maintenance-requests.index') }}" class="btn btn-outline-secondary">Bỏ lọc</a>
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

    <ul class="nav nav-tabs mb-3" id="requestTabs">
        @foreach ($tabs as $idx => $tab)
            <li class="nav-item">
                <button class="nav-link @if($idx===0)active @endif" data-bs-toggle="tab" data-bs-target="#{{ $tab['id'] }}">
                    {{ $tab['label'] }}
                    <span class="badge {{ $tab['badge']['class'] }} ms-1">{{ number_format($tab['badge']['count']) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($tabs as $idx => $tab)
            <div class="tab-pane fade @if($idx===0)show active @endif" id="{{ $tab['id'] }}">
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
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const STORAGE_KEY = 'active_tab_' + window.location.pathname;
        // Khôi phục tab
        const savedTab = sessionStorage.getItem(STORAGE_KEY);

        if (savedTab) {
            const tabButton = document.querySelector(
                `#requestTabs button[data-bs-target="${savedTab}"]`
            );
            if (tabButton) {
                bootstrap.Tab.getOrCreateInstance(tabButton).show();
            }
        }

        // Lưu tab hiện tại
        document.querySelectorAll('#requestTabs button[data-bs-toggle="tab"]')
            .forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (e) {
                    sessionStorage.setItem(
                        STORAGE_KEY,
                        e.target.getAttribute('data-bs-target')
                    );
                });
            });
    });
    </script>
</x-app-layout>