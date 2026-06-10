import $ from 'jquery';

$(function () {
    initSelect2();

    let typingTimer;

    $(document).on(
        'input',
        'input.inline-edit',
        function () {

            let input = $(this);

            clearTimeout(typingTimer);

            typingTimer = setTimeout(function () {
                saveInline(input);
            }, 600);
        }
    );

    $(document).on(
        'blur',
        'input.inline-edit',
        function () {
            console.log('incline change');
            saveInline($(this));
        }
    );

    $(document).on(
        'blur',
        'textarea.inline-edit',
        function () {
            saveInline($(this));
        }
    );

    $(document).on('change', 'select.inline-edit', function () {
        saveInline($(this));
    });

    $(document).on('keydown', 'input.inline-edit', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).blur();
        }
    });

    function saveInline(input) {

        if (!input.length) {
            return;
        }

        let id = input.data('id');
        let field = input.data('field');

        if (!id || !field) {
            console.warn('Missing id or field', input);
            return;
        }

        let value = input.val();

        if (field === 'request_date' && value) {
            value = value.replace('T', ' ');
        }

        if (input.data('saving')) {
            return;
        }

        input.data('saving', true);

        $.ajax({
            url: '/maintenance-requests/inline-update',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: input.data('id'),
                field: input.data('field'),
                value: input.val()
            },
            success: function () {

                input
                    .removeClass('saving')
                    .addClass('inline-edit-success');

                setTimeout(function () {
                    input.removeClass('inline-edit-success');
                }, 800);
            },
            error: function (xhr) {

                console.error(xhr.responseText);

                input.addClass('inline-edit-error');
            },
            complete: function () {
                input.data('saving', false);
            }
        });
    }

    function fillIssueData(container, option) {

        container.find('.issue-description')
            .val(option.data('issue'));

        container.find('.severity-field')
            .val(option.data('severity'))
            .trigger('change');

        container.find('.processing-time')
            .val(option.data('processing'));

        container.find('.solution-description')
            .val(option.data('solution'));

        container.find('.outsourced-provider')
            .val(option.data('handler') || '');
    }

    const originalIssueOptions = $('.issue-selector').html();
    let isUpdating = false;

    $(document).on('change', '.severity-field', function () {

        let severity = String($(this).val()).trim();
        let issueSelector = $('.issue-selector');

        if (isUpdating) return;
        isUpdating = true;
    
        // khôi phục dữ liệu gốc
        issueSelector.html(originalIssueOptions);
    
        if (severity) {
    
            issueSelector.find('option').each(function () {
    
                let optionSeverity = String($(this).data('severity')).trim();
    
                if (
                    optionSeverity &&
                    optionSeverity !== severity
                ) {
                    $(this).remove();
                }
            });
    
            // xoá optgroup rỗng
            issueSelector.find('optgroup').each(function () {
                if ($(this).find('option').length === 0) {
                    $(this).remove();
                }
            });
        }
    
        issueSelector.val('').trigger('change');
        isUpdating = false;
    });

    $(document).on('change', 'table .issue-selector', function () {

        let option = $(this).find(':selected');
        let row = $(this).closest('tr');

        // ===== ISSUE DATA =====
        row.find('.issue-description').val(option.data('issue') || '');
        row.find('.severity-field').val(option.data('severity') || '');
        row.find('.processing-time').val(option.data('processing') || '');
        row.find('.solution-description').val(option.data('solution') || '');

        // ===== FIX MỚI: handler → outsourced_provider =====
        row.find('.outsourced-provider').val(option.data('handler') || '');

        // ===== SAVE INLINE =====
        saveInline($(this));
        saveInline(row.find('.issue-description'));
        saveInline(row.find('.severity-field'));
        saveInline(row.find('.processing-time'));
        saveInline(row.find('.solution-description'));
        saveInline(row.find('.outsourced-provider'));
    });

    $(document).on('change', 'table .form-technician-name', function () {
        let option = $(this).find(':selected');
        let row = $(this).closest('tr');

        // fill SĐT từ data-mobile
        row.find('.technician-mobile')
            .val(option.data('mobile') || '');
        row.find('.technician-email')
            .val(option.data('email') || '');

        saveInline($(this));
        saveInline(row.find('.technician-mobile'));
        saveInline(row.find('.technician-email'));
    });

    $('#createModal').on(
        'change',
        '.issue-selector',
        function () {
            if (isUpdating) return;
            isUpdating = true;

            fillIssueData(
                $('#createModal'),
                $(this).find(':selected')
            );
            isUpdating = false;
        }
    );

    $('#createModal').on('change', '.form-technician-name', function () {

        let option = $(this).find(':selected');

        $('#createModal').find('.technician-mobile')
            .val(option.data('mobile') || '');
        $('#createModal').find('.technician-email')
            .val(option.data('email') || '');
    });

    function renderStatus(status) {
        return `
            <span class="${slaStatusBadges[status] ?? 'badge badge-default'}">
                ${slaStatusNames[status] ?? status}
            </span>
        `;
    }

    $(document).on(
        'change',
        '.confirm-request',
        function () {

            let checkbox = $(this);
            let row = checkbox.closest('tr');
            let checked = checkbox.is(':checked');

            let message = checked
                ? 'Bạn có chắc chắn muốn xác nhận yêu cầu này?'
                : 'Bạn có chắc chắn muốn hủy xác nhận yêu cầu này?';

            if (!confirm(message)) {
                checkbox.prop('checked', !checked);
                return;
            }

            $.ajax({
                url: '/maintenance-requests/confirm',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: $(this).data('id'),
                    confirmed: checked ? 1 : 0
                },
                success: function (res) {
                    let input = row.find('.confirmer-name input');
                    if (res.confirmed) {
                        if (input.length) {
                            input.val(res.confirmer ?? '');
                        } else {
                            row.find('.confirmer-name')
                                .text(res.confirmer ?? '');
                        }
                    } 
                    // else {
                    //     let input = row.find('.confirmer-name input');
                    //     if (input.length) {
                    //         input.val('');
                    //     } else {
                    //         row.find('.confirmer-name')
                    //             .text('');
                    //     }
                    // }
                    row.find('.status-field').html(renderStatus(res.status));
                    row.find('.confirm_checked').html('');
                },
                error: function (xhr) {
                    // Xử lý lỗi trả về từ server, đặc biệt khi response là JSON với thông báo tùy chỉnh
                    let message = 'Có lỗi xảy ra';
                    if (
                        xhr &&
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);

                    // rollback trạng thái checkbox
                    checkbox.prop('checked', !checked);
                }
            });

        }
    );
    
    $(document).on(
        'click',
        '.btn-remind',
        function () {

            let button = $(this);

            $.ajax({
                url: '/maintenance-requests/remind',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: button.data('id')
                },
                success: function () {

                    alert('Đã gửi email nhắc việc');

                    location.reload();

                },
                error: function (xhr) {

                    alert(
                        xhr.responseJSON?.message ||
                        'Có lỗi xảy ra'
                    );

                }
            });

        }
    );

    function initSelect2() {

        $('.select2-branch').select2({
            width: '100%'
        });

        $('.select2-category').select2({
            width: '100%'
        });

    }

    $('#createModal').on('shown.bs.modal', function () {

        $(this)
            .find('.select2-branch')
            .select2({
                dropdownParent: $('#createModal'),
                width: '100%'
            });

        $(this)
            .find('.select2-category')
            .select2({
                dropdownParent: $('#createModal'),
                width: '100%'
            });

    });

    $('#createModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    
        $(this).find('.select2').val(null).trigger('change');
    });

    $(document).on('submit', '#createForm', function (e) {
        e.preventDefault();

        $('#createModal select:disabled').prop('disabled', false);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function () {

                let modalEl = document.getElementById('createModal');
                let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();

                location.reload();
            },
            error: function (xhr) {
                console.log(xhr.responseJSON?.errors);
            }
        });
    });

    document.addEventListener('click', function (e) {

        let btn = e.target.closest('.submitBtn');

        if (!btn) return;

        let form = btn.closest('form');
        if (!form) return;

        let text = btn.querySelector('.btnText');
        let icon = btn.querySelector('.loadingIcon');

        // disable ngay lập tức
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');

        if (text) text.innerText = 'Đang xử lý...';
        if (icon) icon.classList.remove('hidden');

        // cho form submit tiếp
        form.submit();

    });

    $(document).on('change', '.form-branch-name', function () {

        let selected = $(this).find(':selected');

        let code = selected.data('code') || '';
        let technician_name = selected.data('technician_name') || '';

        let modal = $('#createModal');

        modal.find('.form-branch-code').val(code);

        modal.find('.form-technician-name')
            .val(technician_name)
            .trigger('change');
    });

    $(document).on('change', 'table .select2-branch', function () {

        let option = $(this).find(':selected');
        let row = $(this).closest('tr');

        let code = option.data('code') || '';

        let codeInput = row.find('[data-field="branch_code"]');

        codeInput.val(code);

        saveInline($(this));      // branch_name
        saveInline(codeInput);    // branch_code
    });

    $(document).on('click', '.change-status-btn', function (e) {
        e.preventDefault();
        $('#statusRequestId').val($(this).data('id'));
        $('#newStatus').val($(this).data('status'));
        $('#statusLabel').val($(this).text().trim());
        $('#statusNote').val('');
        new bootstrap.Modal(
            document.getElementById('changeStatusModal')
        ).show();

    });

    $('#confirmChangeStatus').on('click', function () {
        let id = $('#statusRequestId').val();
        let status = $('#newStatus').val();
        let note = $('#statusNote').val();
        $.ajax({
            url: `/maintenance-requests/${id}/status`,
            type: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: status,
                note: note
            },
            success: function () {
                location.reload();
            }
        });
    });

    $(document).on('click', '.view-log-btn', function (e) {
        e.preventDefault();
    
        const slaStatusNames = window.slaStatusNames || {};
   
        let id = $(this).data('id');
    
        $.get(`/maintenance-requests/${id}/logs`, function (data) {
    
            let html = '';
    
            data.forEach(log => {
                html += `
                    <tr>
                        <td>${log.created_at ? new Date(log.created_at).toLocaleString('vi-VN', { hour12: false }) : ''}</td>
                        <td>${log.user?.name ?? ''}</td>
                        <td>${slaStatusNames[log.old_status] ?? log.old_status ?? ''}</td>
                        <td>${slaStatusNames[log.new_status] ?? log.new_status ?? ''}</td>
                        <td>${log.note ?? ''}</td>
                    </tr>
                `;
            });
    
            $('#logTableBody').html(html);
    
            new bootstrap.Modal(
                document.getElementById('logModal')
            ).show();
    
        }, 'json');
    });

    $(document).on('change', '.inline-target', function () {

        let row = $(this).closest('tr');

        $.ajax({
            url: '/reports/technician-update',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),

                technician_name:
                    $(this).data('tech'),

                store_count:
                    row.find('[data-field="store_count"]').val(),

                daily_target:
                    row.find('[data-field="daily_target"]').val(),

                monthly_target:
                    row.find('[data-field="monthly_target"]').val()
            },

            success: function () {
                console.log('saved');
            }
        });
    });

    $(document).on('input', '[data-field="monthly_target"]', function () {

        let row = $(this).closest('tr');

        let total = parseInt(
            row.find('.total-cell').text()
        ) || 0;

        let monthlyTarget = parseInt(
            $(this).val()
        ) || 0;

        let vuotDinhMuc = Math.max(
            total - monthlyTarget,
            0
        );

        row.find('.vuot-dinh-muc-cell')
            .text(vuotDinhMuc);
    });

    $(document).on('change', '#month-filter', function () {

        let month = $(this).val();

        let url = new URL(window.location.href);

        url.searchParams.set('month', month);

        window.location.href = url;
    });

    function toggleFormFields() {
        const modal = $('#createModal');
        const key = modal.find('.issue-selector option:selected').data('key');
    
        // Khóa toàn bộ trước
        modal.find('input, textarea')
            .not('.form-branch-name, .issue-selector')
            .prop('readonly', true);
    
        modal.find('select')
            .not('.form-branch-name, .issue-selector, .severity-field')
            .prop('disabled', true);
    
        // Bỏ highlight cũ
        modal.find('.editable-highlight')
            .removeClass('editable-highlight');
    
        // Nếu là OTHER thì mở các trường được phép sửa
        if (key === 'OTHER') {
            modal.find(
                '.severity-field, .issue-description, .solution-description, .processing-time'
            )
            .prop('readonly', false)
            .prop('disabled', false)
            .addClass('editable-highlight');
        }
    }
    
    // Khởi tạo
    toggleFormFields();
    
    // Khi đổi hạng mục
    $(document).on('change', '#createModal .issue-selector', function () {
        toggleFormFields();
    });

    function syncScrollWidth() {
        let tableWidth = $('.table-responsive table')[0].scrollWidth;

        $('.table-scroll-top div').width(tableWidth);
    }

    syncScrollWidth();

    $(window).on('resize', syncScrollWidth);

    $('.table-scroll-top').on('scroll', function () {
        $('.table-responsive').scrollLeft($(this).scrollLeft());
    });

    $('.table-responsive').on('scroll', function () {
        $('.table-scroll-top').scrollLeft($(this).scrollLeft());
    });
});