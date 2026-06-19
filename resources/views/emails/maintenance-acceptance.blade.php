<h2>Yêu cầu bảo trì đã được nghiệm thu</h2>

<p>Mã cơ sở: {{ $request->branch_code }}</p>

<p>Cơ sở: {{ $request->branch_name }}</p>

<p>Hạng mục: {{ $request->item_category }}</p>

@php
    $severities = collect(config('severities'));
    $severityLabel = $severities->where('key', $request->severity)->first()['name'] ?? $request->severity;
@endphp
<p>Loại sự cố: {{ $severityLabel }}</p>

<p>Sự cố: {{ $request->issue_description }}</p>

<p>Kỹ thuật viên: {{ $request->technician_name }}</p>

<p>
    <a href="{{ route('maintenance-requests.show', $request->id) }}">
        Xem chi tiết yêu cầu bảo trì
    </a>
</p>

<p>Yêu cầu đã được nghiệm thu.</p>