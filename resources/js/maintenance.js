import $ from 'jquery';

$(function () {
    initSelect2();
    window.Loading = {
        show(message = 'Đang xử lý...') {
            $('.loading-message').text(message);
            $('#global-loading').removeClass('d-none');
        },
    
        hide() {
            $('#global-loading').addClass('d-none');
        }
    };
    
    $(document)
        .ajaxStart(function () {
            Loading.show();
        })
        .ajaxStop(function () {
            Loading.hide();
        });
    
    $(document).on(
        'submit',
        '.js-loading-form:not(.export-form)',
        function () {
    
            Loading.show(
                $(this).data('loading-text')
                || 'Đang xử lý...'
            );
        }
    );

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

    $(document).on(
        'click',
        '.confirm-request-btn',
        function () {

            let button = $(this);
            let row = button.closest('tr');

            let currentConfirmed = Number(
                button.data('confirmed')
            );

            let newConfirmed = currentConfirmed ? 0 : 1;

            let message = newConfirmed
                ? 'Bạn có chắc chắn muốn xác nhận yêu cầu này?'
                : 'Bạn có chắc chắn muốn hủy xác nhận yêu cầu này?';

            if (!confirm(message)) {
                return;
            }

            $.ajax({
                url: '/maintenance-requests/confirm',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: button.data('id'),
                    confirmed: newConfirmed
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

                    } else {

                        if (input.length) {
                            input.val('');
                        } else {
                            row.find('.confirmer-name')
                                .text('');
                        }
                    }

                    let confirmHtml = `
                        <span class="status_badge ${res.badge_class || 'badge badge-default'}">
                            ${res.status_name || ''}
                        </span><br>
                        ${res.confirmed ? `
                            <span class="badge bg-success">
                                Đã xác nhận
                            </span>
                        ` : `
                            <span class="badge bg-secondary">
                                Chưa xác nhận
                            </span>
                        `}
                    `;

                    row.find('.confirm_checked').html(confirmHtml);

                },
                error: function (xhr) {

                    let message = 'Có lỗi xảy ra';

                    if (
                        xhr &&
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {
                        message = xhr.responseJSON.message;
                    }

                    alert(message);
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
        let branch_email = selected.data('branch_email') || '';

        let modal = $('#createModal');

        modal.find('.form-branch-code').val(code);
        modal.find('.form-branch-email').val(branch_email);

        modal.find('.form-technician-name')
            .val(technician_name)
            .trigger('change');
    });

    // kiểm tra có giá trị trong .form-branch-name thì tự động trigger change
    const $branchName = $('.form-branch-name');
    if ($branchName.length && $branchName.val()) {
        $branchName.trigger('change');
    }

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
        $('#statusSelectWrapper').addClass('d-none');
        $('#statusLabel').closest('.mb-3').removeClass('d-none');
        $('#statusNote').val('');
        new bootstrap.Modal(
            document.getElementById('changeStatusModal')
        ).show();

    });

    $('#confirmChangeStatus').on('click', function () {
        let id = $('#statusRequestId').val();
        let note = $('#statusNote').val();
        let status;
        let tech_mail = $('#technicianSelect').val();
        if (
            $('#statusSelectWrapper')
                .hasClass('d-none')
        ) {
            status = $('#newStatus').val();
        } else {
            status = $('#statusSelect').val();
        }
        
        $.ajax({
            url: `/maintenance-requests/${id}/status`,
            type: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: status,
                note: note,
                tech_mail: tech_mail
            },
            success: function () {
                location.reload();
            }
        });
    });

    $(document).on(
        'click',
        '.admin-change-status-btn',
        function (e) {
    
            e.preventDefault();
    
            $('#statusRequestId')
                .val($(this).data('id'));
    
            let currentStatus =
                $(this).data('current-status');
    
            let options = '';
    
            Object.entries(
                window.slaStatusNames
            ).forEach(([key, value]) => {
    
                options += `
                    <option
                        value="${key}"
                        ${key === currentStatus ? 'selected' : ''}>
                        ${value}
                    </option>
                `;
            });
    
            $('#statusSelect').html(options);
    
            $('#statusLabel')
                .closest('.mb-3')
                .addClass('d-none');
    
            $('#statusSelectWrapper')
                .removeClass('d-none');
    
            $('#statusNote').val('');
    
            new bootstrap.Modal(
                document.getElementById(
                    'changeStatusModal'
                )
            ).show();
        }
    );

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

    $(document).on('change', '#from-date, #to-date', function () {
        let fromDate = $('#from-date').val();
        let toDate = $('#to-date').val();
        let url = new URL(window.location.href);

        if (fromDate) {
            url.searchParams.set('from-date', fromDate);
        }
        if (toDate) {
            url.searchParams.set('to-date', toDate);
        }

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

    const requestTabs = document.getElementById('requestTabs');
    if (requestTabs) {
        const STORAGE_KEY = 'active_tab_' + window.location.pathname;

        // Khôi phục tab
        const savedTab = sessionStorage.getItem(STORAGE_KEY);

        if (savedTab) {

            const tabButton = requestTabs.querySelector(
                `[data-bs-target="${savedTab}"]`
            );

            if (
                tabButton &&
                typeof bootstrap !== 'undefined'
            ) {
                bootstrap.Tab
                    .getOrCreateInstance(tabButton)
                    .show();
            }
        }

        // Lưu tab
        requestTabs
            .querySelectorAll('[data-bs-toggle="tab"]')
            .forEach(tab => {

                tab.addEventListener(
                    'shown.bs.tab',
                    function (e) {

                        sessionStorage.setItem(
                            STORAGE_KEY,
                            e.target.getAttribute(
                                'data-bs-target'
                            )
                        );
                        syncScrollWidth();
                    }
                );
            });
    }

    function syncScrollWidth() {
        let table = $('.tab-pane.active .table-responsive table')[0];
        if (!table) {
            return;
        }
        $('.table-scroll-top div').width(
            table.scrollWidth
        );
    }

    syncScrollWidth();

    $(window).on('resize', syncScrollWidth);

    $('.table-scroll-top').on('scroll', function () {
        $('.table-responsive').scrollLeft($(this).scrollLeft());
    });

    $('.table-responsive').on('scroll', function () {
        $('.table-scroll-top').scrollLeft($(this).scrollLeft());
    });

    $('#exportModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    $(document).on('submit', '#exportForm', function () {

        console.log('submit export');
    
        bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById('exportModal')
        )
        .hide();
    
        Loading.show('Đang xuất báo cáo...');
    
        setTimeout(function () {
            Loading.hide();
        }, 3000);
    });

    $(document).on(
        'click',
        '.acceptance-btn',
        function () {
    
            $('#acceptance_request_id')
                .val($(this).data('id'));
    
            $('#acceptance_result').val('');
    
            $('#acceptance_note').val('');
    
            $('#acceptance-error')
                .addClass('d-none')
                .html('');
        }
    );

    $('#acceptanceModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    $('#submitAcceptance').on(
        'click',
        function () {
            
            $.ajax({
    
                url: '/maintenance-request/acceptance',
    
                type: 'POST',
    
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
    
                    id: $('#acceptance_request_id').val(),
    
                    result: $('#acceptance_result').val(),
    
                    note: $('#acceptance_note').val()
                },
    
                success: function (response) {
    
                    if (response.success) {
    
                        location.reload();
                    }
                },
    
                error: function (xhr) {
    
                    let msg =
                        xhr.responseJSON?.message
                        ?? 'Có lỗi xảy ra';
    
                    $('#acceptance-error')
                        .removeClass('d-none')
                        .html(msg);
                }
            });
        }
    );

    $(document).on(
        'change',
        '#include_saturday, #include_sunday, #include_holiday',
        function () {
            let modal = $(this).closest('.modal');
            let technicianSelect = modal.find('.form-technician-name');
    
            let hasSpecialDay =
                modal.find('#include_saturday').is(':checked') ||
                modal.find('#include_sunday').is(':checked') ||
                modal.find('#include_holiday').is(':checked');
    
            if (hasSpecialDay) {
                 // Chỉ lưu 1 lần trước khi override
                if (!technicianSelect.data('previous-value')) {
                    technicianSelect.data(
                        'previous-value',
                        technicianSelect.val()
                    );
                }

                technicianSelect
                    .val(window.techNgoaiGio)
                    .trigger('change');
            } else {
                let previousValue = technicianSelect.data('previous-value');

                if (previousValue) {
                    technicianSelect
                        .val(previousValue)
                        .trigger('change');

                    technicianSelect.removeData('previous-value');
                }
            }
        }
    );
});