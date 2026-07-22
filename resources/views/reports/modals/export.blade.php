<div class="modal fade" id="exportsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="GET" id="exportForm" autocomplete="off">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xuất báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-12 d-flex align-items-center gap-3">
                            <label for="report_type" class="form-label mb-0" style="white-space:nowrap;">Loại báo cáo</label>
                            <select class="form-select select2-branch w-auto flex-grow-1" id="report_type" name="report_type" style="min-width:220px;">
                                <option value="{{ route('maintenance-requests.export') }}" data-type="summary">Tổng hợp yêu cầu</option>
                                <option value="{{ route('reports.technician-export') }}" data-type="tech">Báo cáo kỹ thuật viên</option>
                                <option value="{{ route('maintenance.export-fromto') }}" data-type="branch">Báo cáo thông kê</option>
                                <option value="{{ route('maintenance-requests.export-kpi') }}" data-type="tech">KPI Kỹ thuật viên</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">Ngày yêu cầu (Từ)</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">Ngày yêu cầu (Đến)</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="from_date_completed" class="form-label">Ngày hoàn thành (Từ)</label>
                            <input type="date" id="from_date_completed" name="from_date_completed" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date_completed" class="form-label">Ngày hoàn thành (Đến)</label>
                            <input type="date" id="to_date_completed" name="to_date_completed" class="form-control">
                        </div>
                    </div>

                    @php
                        $notExportEmail = 'liemhoang.support.hcm@tocotocotea.com';
                        $technicians = config('technician');
                        // Lọc kỹ thuật viên có key là số và không bị loại trừ bởi email đặc biệt
                        $techList = array_filter($technicians, function ($tech, $key) use ($notExportEmail) {
                            return (is_int($key) || ctype_digit((string) $key)) && (!isset($tech['email']) || $tech['email'] !== $notExportEmail);
                        }, ARRAY_FILTER_USE_BOTH);
                    @endphp
                    <div class="mb-3" id="tech-filter-section">
                        <label class="form-label">Kỹ thuật viên</label>
                        <input type="text" id="system-tech-search" class="form-control mb-2" placeholder="Tìm kỹ thuật viên">
                        <div class="border rounded p-2" style="max-height:250px;overflow:auto">
                            @foreach($techList as $tech)
                                <label class="d-flex align-items-center gap-2 p-2 border rounded mb-1 system-tech-item" style="cursor:pointer;">
                                    <input type="checkbox" name="tech_emails[]" value="{{ $tech['email'] }}">
                                    <span>
                                        <strong>{{ $tech['name'] }}</strong><br>
                                        <small class="text-muted">{{ $tech['email'] }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Hidden input to default report type (if needed by JS) -->
                    <input type="hidden" name="report_type" value="maintenance_request">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">Xuất Excel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<style>
    .tech-item:hover {
        background: #f8f9fa;
    }
</style>