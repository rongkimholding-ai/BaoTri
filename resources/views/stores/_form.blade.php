@php
    $fields = [
        [
            'col' => 'col-md-4',
            'type' => 'number',
            'label' => 'Mã cửa hàng',
            'name' => 'code',
        ],
        [
            'col' => 'col-md-4',
            'type' => 'select',
            'label' => 'Miền <span class="text-danger">*</span>',
            'name' => 'area',
            'options' => [
                '' => '-- Chọn --',
                'north' => 'Miền Bắc',
                'south' => 'Miền Nam',
            ],
            'required' => true,
        ],
        [
            'col' => 'col-md-4',
            'type' => 'text',
            'label' => 'Khu vực',
            'name' => 'region',
        ],
        [
            'col' => 'col-md-12',
            'type' => 'text',
            'label' => 'Tên cửa hàng <span class="text-danger">*</span>',
            'name' => 'name',
            'required' => true,
        ],
        [
            'col' => 'col-md-12',
            'type' => 'email',
            'label' => 'Email cửa hàng <span class="text-danger">*</span>',
            'name' => 'email',
            'required' => true,
        ],
        [
            'col' => 'col-md-6',
            'type' => 'text',
            'label' => 'AM',
            'name' => 'am_name'
        ],
        [
            'col' => 'col-md-6',
            'type' => 'email',
            'label' => 'Email AM',
            'name' => 'am_email'
        ],
        [
            'col' => 'col-md-6',
            'type' => 'text',
            'label' => 'OM',
            'name' => 'om_name'
        ],
        [
            'col' => 'col-md-6',
            'type' => 'email',
            'label' => 'Email OM',
            'name' => 'om_email'
        ],
        [
            'col' => 'col-md-6',
            'type' => 'text',
            'label' => 'Kỹ thuật viên',
            'name' => 'technician_name'
        ],
        [
            'col' => 'col-md-6',
            'type' => 'email',
            'label' => 'Email mua sắm',
            'name' => 'muasam_email'
        ]
    ];
@endphp

<div class="row">
    @foreach($fields as $field)
        <div class="{{ $field['col'] }} mb-3">
            <label class="form-label">{!! $field['label'] !!}</label>
            @if(isset($field['type']) && $field['type'] === 'select')
                <select name="{{ $field['name'] }}" class="form-select"{{ isset($field['required']) && $field['required'] ? ' required' : '' }}>
                    @foreach($field['options'] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}"
                            @if(old($field['name'], $store->{$field['name']} ?? '') == $optionValue) selected @endif>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
            @else
                <input
                    type="{{ $field['type'] }}"
                    name="{{ $field['name'] }}"
                    class="form-control"
                    value="{{ old($field['name'], $store->{$field['name']} ?? '') }}"
                    {{ isset($field['required']) && $field['required'] ? 'required' : '' }}
                >
            @endif
        </div>
    @endforeach
</div>