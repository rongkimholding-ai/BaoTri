<div class="modal fade" id="bulkAcceptanceModal" tabindex="-1" aria-labelledby="bulkAcceptanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form
                id="bulkAcceptanceForm"
                method="POST"
                action="{{ route('maintenance-system.bulk-acceptance') }}"
            >
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkAcceptanceModalLabel">Nghiệm thu hàng loạt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="acceptanceMonth" class="form-label">Tháng</label>
                        <input
                            type="number"
                            class="form-control"
                            id="acceptanceMonth"
                            name="month"
                            min="1"
                            max="12"
                            value="{{ now()->month }}"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label for="acceptanceYear" class="form-label">Năm</label>
                        <input
                            type="number"
                            class="form-control"
                            id="acceptanceYear"
                            name="year"
                            min="2000"
                            max="{{ now()->year }}"
                            value="{{ now()->year }}"
                            required
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="btnBulkAcceptance">
                        Nghiệm thu toàn bộ tháng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('bulkAcceptanceForm')
    .addEventListener('submit', async function (event) {
        event.preventDefault();

        const form = this;
        const button = document.getElementById('btnBulkAcceptance');
        const month = form.elements.month.value;
        const year = form.elements.year.value;

        if (!confirm(
            `Xác nhận nghiệm thu đạt toàn bộ bản ghi đủ điều kiện ` +
            `trong tháng ${month}/${year}?`
        )) {
            return;
        }

        button.disabled = true;
        button.textContent = 'Đang xử lý...';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || form.querySelector('input[name="_token"]')?.value
                || '';

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
                body: new FormData(form)
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                alert(data.message || `Không thể nghiệm thu (mã ${response.status}).`);
                return;
            }

            alert(data.message || 'Không nhận được thông báo kết quả.');

            if (data.success) {
                window.location.reload();
            }

        } catch (error) {
            alert('Không thể xử lý yêu cầu. Vui lòng kiểm tra lại.');
        } finally {
            button.disabled = false;
            button.textContent = 'Nghiệm thu toàn bộ tháng';
        }
    });
</script>
