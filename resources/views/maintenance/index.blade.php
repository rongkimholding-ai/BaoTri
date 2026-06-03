<x-app-layout>
    <x-slot name="header">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="d-flex justify-content-between mb-3">
        <h3>Danh sách bảo trì</h3>

        <div class="d-flex gap-2">
            @can('export excel')
            <a href="{{ route('maintenance-requests.export') }}" class="btn btn-success">
                Xuất Excel
            </a>
            @endcan
            
            @can('create data')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                Thêm mới
            </button>
            @endcan
        </div>
    </div>
    </x-slot>
    <div class="py-4">
    <div class="table-responsive">
        <table id="tbData" class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>{{ config('maintenance.fields.id') }}</th>
                    <th>{{ config('maintenance.fields.branch_code') }}</th>
                    <th class="branch_name_class">{{ config('maintenance.fields.branch_name') }}</th>
                    <th>{{ config('maintenance.fields.request_date') }}</th>
                    <th>{{ config('maintenance.fields.item_category') }}</th>
                    <th>{{ config('maintenance.fields.issue_description') }}</th>
                    <th>{{ config('maintenance.fields.standard_completion_time') }}</th>
                    <th>{{ config('maintenance.fields.severity') }}</th>
                    <th>{{ config('maintenance.fields.technician_name') }}</th>
                    <th>{{ config('maintenance.fields.technician_email') }}</th>
                    <th>{{ config('maintenance.fields.solution_description') }}</th>
                    <th>{{ config('maintenance.fields.actual_completion_date') }}</th>
                    <th>{{ config('maintenance.fields.actual_duration') }}</th>
                    <th>{{ config('maintenance.fields.sla_status') }}</th>
                    <th>{{ config('maintenance.fields.delay_reason') }}</th>
                    <th>{{ config('maintenance.fields.outsourced_provider') }}</th>
                    <th>{{ config('maintenance.fields.acceptance_result') }}</th>
                    <th class="confirm_checked">{{ config('maintenance.fields.confirmed_checked') }}</th>
                    <th>{{ config('maintenance.fields.acceptance_confirmed_by') }}</th>
                    <th>{{ config('maintenance.fields.reminder') }}</th>
                    <th>{{ config('maintenance.fields.created_at') }}</th>
                    <th>{{ config('maintenance.fields.updated_at') }}</th>
                    @can('delete data')
                    <th>Thao tác</th>
                    @endcan
                </tr>
            </thead>

            <tbody>
                @php
                    $canUpdate = auth()->user()->can('update data');
                @endphp
                @if (!empty($requests))
                @foreach($requests as $item)
                    <tr data-id="{{ $item->id }}">

                        <td>{{ $item->id }}</td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="branch_code"
                                value="{{ $item->branch_code }}">
                            @else
                                {{ $item->branch_code }}
                            @endif
                        </td>

                        <td class="branch_name_class">
                        @if($canUpdate)
                            <select class="form-control inline-edit select2-branch" data-id="{{ $item->id }}"
                                data-field="branch_name">
                                <option value="">-- Chọn cơ sở --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['name'] }}" @if($item->branch_name == $store['name']) selected @endif>
                                        {{ $store['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @else
                                {{ $item->branch_name }}
                            @endif
                        </td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit date-field" data-id="{{ $item->id }}"
                                data-field="request_date" type="date" value="{{ $item->request_date }}">
                            @else
                                {{ $item->request_date ? date('d/m/Y', strtotime($item->request_date)) : $item->request_date }}
                            @endif
                        </td>

                        <td class="item_category_class">
                            @if($canUpdate)
                            <select class="form-control inline-edit select2-category issue-selector" data-id="{{ $item->id }}"
                                data-field="item_category">

                                <option value="">Chọn hạng mục</option>

                                @foreach($checks as $check)

                                    <optgroup label="{{ $check['name'] }}">

                                        @foreach($check['issues'] as $issue)

                                            @php
                                                $value = $check['key'] . '|' . $issue['key'];
                                            @endphp

                                            <option value="{{ $issue['name'] }}" data-category="{{ $check['name'] }}"
                                                data-issue="{{ $issue['name'] }}" data-severity="{{ $issue['severity'] }}"
                                                data-processing="{{ $issue['processing_time'] }}"
                                                data-solution="{{ $issue['solution'] }}"
                                                @selected($item->issue_description == $issue['name'])>
                                                {{ $check['name'] }} - {{ $issue['name'] }}
                                            </option>

                                        @endforeach

                                    </optgroup>

                                @endforeach

                            </select>
                            @else
                                {{ $item->item_category }}
                            @endif
                        </td>

                        <td>
                        @if($canUpdate)
                            <textarea class="form-control inline-edit issue-description" data-id="{{ $item->id }}"
                                data-field="issue_description" rows="3">{{ $item->issue_description }}</textarea>
                            @else
                            <textarea rows="3" readonly> {{ $item->issue_description }}</textarea>
                            @endif
                        </td>

                        <td>
                        @if($canUpdate)
                            <input class="form-control inline-edit processing-time" data-id="{{ $item->id }}"
                                data-field="standard_completion_time" value="{{ $item->standard_completion_time }}">
                            @else
                                {{ $item->standard_completion_time }}
                            @endif
                        </td>

                        <td>
                        @if($canUpdate)
                            <input class="form-control inline-edit severity-field" data-id="{{ $item->id }}"
                                data-field="severity" value="{{ $item->severity }}">
                            @else
                                {{ $item->severity }}
                            @endif
                        </td>

                        <td>
                        @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="technician_name"
                                value="{{ $item->technician_name }}">
                                @else
                                {{ $item->technician_name }}
                            @endif
                        </td>

                        <td>
                        @if($canUpdate)
                            <input
                                class="form-control inline-edit"
                                data-id="{{ $item->id }}"
                                data-field="technician_email"
                                value="{{ $item->technician_email }}">
                        @else
                            {{ $item->technician_email }}
                        @endif
                        </td>

                        <td>
                        @if($canUpdate)
                            <textarea class="form-control inline-edit solution-description" data-id="{{ $item->id }}"
                                data-field="solution_description" rows="3">{{ $item->solution_description }}</textarea>
                            @else
                                
                            <textarea rows="3" readonly>{{ $item->solution_description }}</textarea>
                            @endif
                        </td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit date-field" data-id="{{ $item->id }}"
                                data-field="actual_completion_date" type="date" value="{{ $item->actual_completion_date }}">
                            @else
                                {{ $item->actual_completion_date }}
                            @endif
                          </td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="actual_duration"
                                value="{{ $item->actual_duration }}">
                            @else
                                {{ $item->actual_duration }}
                            @endif
                            </td>

                        <td>
                            @if($canUpdate)
                            <select class="form-select inline-edit" data-id="{{ $item->id }}" data-field="sla_status">

                                <option value="Đúng hạn" {{ $item->sla_status == 'Đúng hạn' ? 'selected' : '' }}>
                                    Đúng hạn
                                </option>

                                <option value="Trễ hạn" {{ $item->sla_status == 'Trễ hạn' ? 'selected' : '' }}>
                                    Trễ hạn
                                </option>

                            </select>
                            @else
                                {{ $item->sla_status }}
                            @endif
                        </td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="delay_reason"
                                value="{{ $item->delay_reason }}">
                            @else
                                {{ $item->delay_reason }}
                            @endif
                        </td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="outsourced_provider"
                                value="{{ $item->outsourced_provider }}">
                            @else
                                {{ $item->outsourced_provider }}
                            @endif
                        </td>

                        <td>
                            @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="acceptance_result"
                                value="{{ $item->acceptance_result }}">
                            @else
                                {{ $item->acceptance_result }}
                            @endif
                        </td>

                        <td class="confirm_checked">
                            @can('confirm maintenance')
                                <input
                                    type="checkbox"
                                    class="confirm-request"
                                    data-id="{{ $item->id }}"
                                    {{ $item->is_confirmed ? 'checked' : '' }}>
                            @else
                                @if($item->is_confirmed)
                                    <span class="badge bg-success">
                                        Đã xác nhận
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        Chưa xác nhận
                                    </span>
                                @endif

                            @endcan

                        </td>

                        <td class="confirmer-name">
                            @if($canUpdate)
                            <input class="form-control inline-edit" data-id="{{ $item->id }}"
                                data-field="acceptance_confirmed_by" value="{{ $item->acceptance_confirmed_by }}">
                            @else
                                {{ $item->acceptance_confirmed_by }}
                            @endif
                        </td>

                        <td>

                            <button
                                class="btn btn-warning btn-sm btn-remind"
                                data-id="{{ $item->id }}">

                                Nhắc việc

                            </button>

                            <div>

                                Đã nhắc:
                                {{ $item->reminder_count }}

                            </div>

                        </td>

                        <td>{{ $item->created_at?->format('d/m/Y H:i') }}</td>

                        <td>{{ $item->updated_at?->format('d/m/Y H:i') }}</td>
                       
                        @can('delete data')
                        <td class="text-center">
                            <form action="{{ route('maintenance-requests.destroy', $item->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Xóa bản ghi này?')">
                                    Xóa
                                </button>
                            </form>
                        </td>
                        @endcan

                    </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
    </div>

    @include('maintenance.modals.create')

</x-app-layout>