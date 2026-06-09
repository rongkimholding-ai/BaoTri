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
            .val(option.data('severity'));

        container.find('.processing-time')
            .val(option.data('processing'));

        container.find('.solution-description')
            .val(option.data('solution'));

        container.find('.outsourced-provider')
            .val(option.data('handler') || '');
    }

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
        console.log(option.data('mobile'));

        // fill SĐT từ data-mobile
        row.find('.technician-mobile')
            .val(option.data('mobile') || '');

        saveInline($(this));
        saveInline(row.find('.technician-mobile'));
    });

    $('#createModal').on(
        'change',
        '.issue-selector',
        function () {

            fillIssueData(
                $('#createModal'),
                $(this).find(':selected')
            );

        }
    );

    $('#createModal').on('change', '.form-technician-name', function () {

        let option = $(this).find(':selected');

        $('#createModal').find('.technician-mobile')
            .val(option.data('mobile') || '');
    });

    $(document).on(
        'change',
        '.confirm-request',
        function () {

            let checkbox = $(this);
            let row = checkbox.closest('tr');
            $.ajax({
                url: '/maintenance-requests/confirm',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: $(this).data('id'),
                    confirmed: $(this).is(':checked')
                },
                success: function (res) {
                    let input = row.find('.confirmer-name input');
                    console.log(res.confirmed);
                    if (res.confirmed) {
                        if (input.length) {
                            input.val(res.confirmer ?? '');
                        } else {
                            row.find('.confirmer-name')
                                .text(res.confirmer ?? '');
                        }
                    } else {
                        let input = row.find('.confirmer-name input');

                        if (input.length) {
                            input.val('');
                        } else {
                            row.find('.confirmer-name')
                                .text('');
                        }
                    }
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
        console.log('CLICK OK');

        let id = $(this).data('id');

        $.get(`/maintenance-requests/${id}/logs`, function (data) {

            let html = '';

            data.forEach(log => {
                html += `
                    <tr>
                        <td>${log.created_at ? new Date(log.created_at).toLocaleString('vi-VN', { hour12: false }) : ''}</td>
                        <td>${log.user?.name ?? ''}</td>
                        <td>${log.old_status ?? ''}</td>
                        <td>${log.new_status}</td>
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
            .not('.form-branch-name, .issue-selector')
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