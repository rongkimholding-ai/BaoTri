<div class="modal fade" id="exportsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="GET" id="exportForm">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Xuất báo cáo
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Loại báo cáo</label>
                            <select class="form-select select2-branch" id="report_type" name="report_type">
                                <option value="{{ route('maintenance-requests.export') }}" data-type="summary">
                                    Tổng hợp yêu cầu
                                </option>
                                <option value="{{ route('reports.technician-export') }}" data-type="tech">
                                    Báo cáo kỹ thuật viên
                                </option>
                                <option value="{{ route('maintenance.export-fromto') }}" data-type="branch">
                                    Báo cáo thông kê
                                </option>
                                <!-- <option value="{{ route('maintenance-requests.export-kpi') }}" data-type="tech">
                                    KPI Kỹ thuật viên
                                </option> -->
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Từ ngày</label>

                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                Đến ngày
                            </label>

                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>

                    </div>

                    @php
                        use Illuminate\Support\Facades\Auth;
                        $user = Auth::user();
                        $notExportEmail = 'liemhoang.support.hcm@tocotocotea.com';
                        $technicians = config('technician');
                        // Remove all non-numeric (like 'ngoai_gio') keys
                        $techList = array_filter($technicians, function ($tech, $key) use ($notExportEmail) {
                            $isNumericKey = is_int($key) || ctype_digit((string) $key);
                            $isNotExportEmail = !(isset($tech['email']) && $tech['email'] === $notExportEmail);
                            return $isNumericKey && $isNotExportEmail;
                        }, ARRAY_FILTER_USE_BOTH);

                    @endphp
                    <div class="mb-3" id="tech-filter-section">
                        <label class="form-label">
                            Kỹ thuật viên
                        </label>

                        <!-- <select
                            class="form-control select2"
                            name="tech_emails[]"
                            multiple>

                            @foreach($techList as $tech)
                                <option value="{{ $tech['email'] }}">
                                    {{ $tech['name'] }}
                                </option>
                            @endforeach

                        </select> -->

                        <input type="text" id="tech-search" class="form-control mb-2" placeholder="Tìm kỹ thuật viên">

                        <div class="border rounded p-2" style="max-height:250px;overflow:auto">

                            @foreach($techList as $tech)

                                <label class="d-flex align-items-center gap-2 p-2 border rounded mb-1 tech-item"
                                    style="cursor:pointer;">

                                    <input type="checkbox" name="tech_emails[]" value="{{ $tech['email'] }}">

                                    <span>
                                        <strong>{{ $tech['name'] }}</strong><br>
                                        <small class="text-muted">
                                            {{ $tech['email'] }}
                                        </small>
                                    </span>

                                </label>

                            @endforeach

                        </div>
                    </div>

                    <input type="hidden" name="report_type" value="maintenance_request">

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Đóng
                    </button>

                    <button type="submit" class="btn btn-success">
                        Xuất Excel
                    </button>

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