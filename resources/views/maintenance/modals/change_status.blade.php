<div class="modal fade" id="changeStatusModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Thay đổi trạng thái
                </h5>
            </div>

            <div class="modal-body">

                <input type="hidden" id="statusRequestId">

                <input type="hidden" id="newStatus">

                <div class="mb-3">

                    <label class="form-label">
                        Trạng thái mới
                    </label>

                    <input type="text" id="statusLabel" class="form-control" readonly>
                </div>

                @php
                    use Illuminate\Support\Facades\Auth;
                    $user = Auth::user();
                    $baotriEmail = 'baotri@tocotocotea.com';
                    $technicians = config('technician');
                    // Remove all non-numeric (like 'ngoai_gio') keys
                    $techList = array_filter($technicians, function ($key) {
                        return is_int($key) || ctype_digit((string) $key);
                    }, ARRAY_FILTER_USE_KEY);
                @endphp

                @if($user && ($user->email === $baotriEmail || $user->hasRole('admin')))
                    <div class="mb-3">
                        <label class="form-label">Kỹ thuật viên phụ trách</label>
                        <select id="technicianSelect" name="technician_email" class="form-select">
                            <option value="">-- Chọn kỹ thuật viên --</option>
                            @foreach($techList as $tech)
                                <option value="{{ $tech['email'] }}">{{ $tech['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif


                <div class="mb-3 d-none" id="statusSelectWrapper">
                    <label class="form-label">
                        Chọn trạng thái
                    </label>

                    <select id="statusSelect" class="form-select">
                    </select>
                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Ghi chú
                    </label>

                    <textarea id="statusNote" rows="4" class="form-control"></textarea>

                </div>

                <div class="mb-3 d-none" id="imageUploadWrapper">

                    <label class="form-label">
                        Ảnh hoàn thành
                    </label>

                    <input type="file" id="completionImages" class="form-control" multiple accept="image/*">

                    <small class="text-muted">
                        Có thể chọn nhiều ảnh
                    </small>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Hủy
                </button>

                <button type="button" id="confirmChangeStatus" class="btn btn-primary">
                    Xác nhận
                </button>

            </div>

        </div>

    </div>

</div>