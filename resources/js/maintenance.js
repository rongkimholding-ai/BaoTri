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

    $(document).on('select2:open', function () {
        setTimeout(function () {
            document.querySelector('.select2-container--open .select2-search__field')?.focus();
        }, 0);
    });

    function fillIssueData(container, option) {

        container.find('.issue-description')
            .val(option.data('issue'));

        // container.find('.severity-field')
        //     .val(option.data('severity'))
        //     .trigger('change');
        container.find('.severity-field')
        .val(option.data('severity'));

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

        // kiểm tra nếu option được chọn của issueSelector có data-key là 'OTHER' hoặc value là 'other_store' thì bỏ qua không xử lý
        // let $selected = issueSelector.find('option:selected');
        // if ($selected.data('key') === 'OTHER' || issueSelector.val() === 'other_store') {
        //     return;
        // }

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

            let $modal = $('#createModal');
            let $selected = $(this).find(':selected');
            fillIssueData($modal, $selected);

            // Nếu select "OTHER" thì show trường severity
            // if ($selected.val() === 'other_store' || $selected.data('key') === 'OTHER') {
            //     $modal.find('.severity-field').closest('.mb-3').removeClass('hidden');
            // } else {
            //     $modal.find('.severity-field').closest('.mb-3').addClass('hidden');
            // }
    

            // Check severity data
            let severity = $selected.data('severity');
            if (severity === '1A') {
                // Auto check T7, CN
                $modal.find('#include_saturday').prop('checked', true);
                $modal.find('#include_sunday').prop('checked', true);
            } else {
                $modal.find('#include_saturday').prop('checked', false);
                $modal.find('#include_sunday').prop('checked', false);
            }
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
        $('.select2-branch, .select2-category')
        .not('#createModal .select2-branch, #createModal .select2-category')
        .select2({
            width: '100%'
        });
    }

    function initSelect2Modal() {
        let $modal = $('#createModal');
        $modal.find('.select2-branch').select2({
            dropdownParent: $modal,
            width: '100%'
        });
        $modal.find('.select2-category').select2({
            dropdownParent: $modal,
            width: '100%'
        });
        
        let $modalSystem = $('#maintenanceModal');
        $modalSystem.find('.select2-issue-name').select2({
            width: '100%',
            dropdownParent: $modalSystem
        });
        $modalSystem.find('.select2-branch-system').select2({
            width: '100%',
            dropdownParent: $modalSystem
        });
        $modalSystem.find('.select2-system-tech').select2({
            width: '100%',
            dropdownParent: $modalSystem
        });

    }

    $('#createModal').on('shown.bs.modal', function () {
        initSelect2Modal();
    });

    $('#createModal').on('hidden.bs.modal', function () {
        const $modal = $(this);
        $modal.find('form')[0].reset();
        $modal.find('select').each(function () {
            $(this).val(null).trigger('change.select2');
        });
        $('#create-form-errors')
        .addClass('d-none')
        .html('');
    });

    let currentEditId = null;

    $('#editModal').on('show.bs.modal', function (e) {

        currentEditId = $(e.relatedTarget).data('id');

        const $form = $('#editForm');
        const baseAction = $form.data('base-action') || '/maintenance-requests/{id}';

        $form.attr('action', baseAction.replace('{id}', currentEditId));

        $(this).find('.modal-body').html(`
            <div class="text-center py-5">
                <div class="spinner-border"></div>
            </div>
        `);

    });

    $('#editModal').on('shown.bs.modal', function () {

        const $modal = $(this);

        $.get(`/maintenance-requests/${currentEditId}/edit`, function (html) {

            $modal.find('.modal-body').html(html);

            $modal.find('.select2-category, .select2-branch').select2({
                dropdownParent: $modal,
                width: '100%'
            });

            toggleFormEditFields();
        });

    });

    $('#editModal').on('hidden.bs.modal', function () {
        const $modal = $(this);
        $modal.find('form')[0].reset();
        $modal.find('select').each(function () {
            $(this).trigger('change.select2');
        });
        $('#edit-form-errors')
        .addClass('d-none')
        .html('');
    });

    $(document).on('submit', '#createForm', function (e) {
        e.preventDefault();

        $('#createModal select:disabled').prop('disabled', false);

        // Nếu chọn "Cửa hàng khác", lấy giá trị từ input other_branch_name, other_branch_code, other_branch_email và truyền vào input ẩn branch_name, branch_code, branch_email,
        // đồng thời xoá input select branch_name để không trùng name, tránh truyền 2 giá trị branch_name khi submit
        let branchSelect = $('#branch_name_select');
        if (branchSelect.val() === 'other_store') {
            let form = $(this);

            // Lấy các giá trị nhập thủ công
            let otherBranchName = $('#other_branch_name').val();
            let otherBranchCode = $('#other_branch_code').val();
            let otherBranchEmail = $('#other_branch_email').val();

            // Xoá select name="branch_name" để không trùng với input ẩn
            form.find('select[name="branch_name"]').prop('disabled', true);

            // Xoá hidden hoặc input branch_name cũ (không phải hidden-other-branch)
            form.find('input[name="branch_name"]:not(.hidden-other-branch)').remove();
            form.find('input[name="branch_code"]:not(.hidden-other-branch)').remove();
            form.find('input[name="branch_email"]:not(.hidden-other-branch)').remove();

            // Tạo/cập nhật input ẩn branch_name
            let hiddenName = form.find('input[name="branch_name"].hidden-other-branch');
            if (hiddenName.length === 0) {
                hiddenName = $('<input>').attr({
                    type: 'hidden',
                    name: 'branch_name'
                }).addClass('hidden-other-branch');
                form.append(hiddenName);
            }
            hiddenName.val(otherBranchName || '');

            // Tạo/cập nhật input ẩn branch_code
            let hiddenCode = form.find('input[name="branch_code"].hidden-other-branch');
            if (hiddenCode.length === 0) {
                hiddenCode = $('<input>').attr({
                    type: 'hidden',
                    name: 'branch_code'
                }).addClass('hidden-other-branch');
                form.append(hiddenCode);
            }
            hiddenCode.val(otherBranchCode || '');

            // Tạo/cập nhật input ẩn branch_email
            let hiddenEmail = form.find('input[name="branch_email"].hidden-other-branch');
            if (hiddenEmail.length === 0) {
                hiddenEmail = $('<input>').attr({
                    type: 'hidden',
                    name: 'branch_email'
                }).addClass('hidden-other-branch');
                form.append(hiddenEmail);
            }
            hiddenEmail.val(otherBranchEmail || '');

        } else {
            // Nếu không chọn "other_store", enable lại select branch_name
            $(this).find('select[name="branch_name"]').prop('disabled', false);
            // Xoá các input ẩn nếu có
            $(this).find('input.hidden-other-branch').remove();
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
        
            success: function () {
        
                $('#create-form-errors')
                    .addClass('d-none')
                    .html('');
        
                let modalEl = document.getElementById('createModal');
                let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        
                modal.hide();
        
                location.reload();
            },
        
            error: function (xhr) {
        
                let errorBox = $('#create-form-errors');
        
                errorBox.html('');
        
                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.errors
                ) {
        
                    let html = '<ul class="mb-0">';
        
                    $.each(
                        xhr.responseJSON.errors,
                        function (field, messages) {
        
                            messages.forEach(function (message) {
                                html += `<li>${message}</li>`;
                            });
        
                        }
                    );
        
                    html += '</ul>';
        
                    errorBox
                        .removeClass('d-none')
                        .html(html);
        
                } else {
        
                    errorBox
                        .removeClass('d-none')
                        .html(xhr.responseJSON?.message || 'Có lỗi xảy ra');
                }
        
            }
        });
    });

    $(document).on('submit', '#editForm', function (e) {
        e.preventDefault();

        $('#editForm select:disabled').prop('disabled', false);
        console.log($(this).attr('action'));
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
        
            success: function () {
        
                $('#edit-form-errors')
                    .addClass('d-none')
                    .html('');
        
                let modalEl = document.getElementById('editModal');
                let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        
                modal.hide();
        
                location.reload();
            },
        
            error: function (xhr) {
        
                let errorBox = $('#edit-form-errors');
        
                errorBox.html('');
        
                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.errors
                ) {
        
                    let html = '<ul class="mb-0">';
        
                    $.each(
                        xhr.responseJSON.errors,
                        function (field, messages) {
        
                            messages.forEach(function (message) {
                                html += `<li>${message}</li>`;
                            });
        
                        }
                    );
        
                    html += '</ul>';
        
                    errorBox
                        .removeClass('d-none')
                        .html(html);
        
                } else {
        
                    errorBox
                        .removeClass('d-none')
                        .html(xhr.responseJSON?.message || 'Có lỗi xảy ra');
                }
        
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

    function toggleImageUpload(status) {
        if (status == window.slaStatusCodes.WAITING_CONFIRM) {
            $('#imageUploadWrapper').removeClass('d-none');
        } else {
            $('#imageUploadWrapper').addClass('d-none');
            $('#completionImages').val('');
        }
    }

    $(document).on('change', '#statusSelect', function () {
        toggleImageUpload($(this).val());
    });

    // Gửi log client (POST, timeout 3s), thêm token mặc định
    function sendClientLog(data) {
        const token = $('meta[name="csrf-token"]').attr('content');
        $.post({
            url: '/client-log',
            timeout: 3000,
            data: { _token: token, ...data }
        });
    }

    // Gửi log mạng với các thông tin chi tiết truy cập
    function logNetworkInfo(step) {
        const nav = performance.getEntriesByType('navigation')[0] || {};
        sendClientLog({
            type: 'network',
            step,
            time: new Date().toISOString(),
            online: navigator.onLine,
            connectionType: navigator.connection?.effectiveType || null,
            downlink: navigator.connection?.downlink || null,
            rtt: navigator.connection?.rtt || null,
            pageLoad: nav.duration || null,
            userAgent: navigator.userAgent
        });
    }

    let selectingImages = false, upload100Logged = false;

    // Khi bắt đầu chọn ảnh: disable các nút
    $(document).on('click', '#completionImages', function () {
        selectingImages = true;
        $('#confirmChangeStatus, #statusSelect').prop('disabled', true);
    });

    // Khi đã chọn xong ảnh: enable nút, log số lượng/size ảnh
    $(document).on('change', '#completionImages', function () {
        selectingImages = false;
        $('#confirmChangeStatus, #statusSelect').prop('disabled', false);
        const files = this.files;
        sendClientLog({
            type: 'image_selected',
            image_count: files.length,
            total_size: Array.from(files).reduce((t, f) => t + f.size, 0)
        });
    });

    // Đóng modal: reset lại trường ảnh và trạng thái
    $('#changeStatusModal').on('hidden.bs.modal', function () {
        $('#completionImages').val(null);
        $('#imageUploadWrapper').addClass('d-none');
        selectingImages = false;
        upload100Logged = false;
    });

    $(document).on('click', '.change-status-btn', function (e) {

        e.preventDefault();
    
        let status = $(this).data('status');
    
        $('#statusRequestId').val($(this).data('id'));
        $('#newStatus').val(status);
        $('#statusLabel').val($(this).text().trim());
    
        $('#statusSelectWrapper').addClass('d-none');
        $('#statusLabel').closest('.mb-3').removeClass('d-none');
        $('#statusNote').val('');
    
        toggleImageUpload(status);
    
        new bootstrap.Modal(
            document.getElementById('changeStatusModal')
        ).show();
    });

    // $('#confirmChangeStatus').on('click', function () {
    //     let id = $('#statusRequestId').val();
    //     let note = $('#statusNote').val();
    //     let tech_mail = $('#technicianSelect').val();
    //     let status;
    //     let btn = $('#confirmChangeStatus');
    //     btn.prop('disabled', true);
    
    //     if ($('#statusSelectWrapper').hasClass('d-none')) {
    //         status = $('#newStatus').val();
    //     } else {
    //         status = $('#statusSelect').val();
    //     }
    
    //     if (
    //         status == window.slaStatusCodes.WAITING_CONFIRM &&
    //         $('#completionImages')[0].files.length === 0
    //     ) {
    //         alert('Vui lòng tải lên ít nhất 1 ảnh.');
    //         return;
    //     }
    
    //     let formData = new FormData();
    
    //     formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    //     formData.append('_method', 'PATCH');
    //     formData.append('status', status);
    //     formData.append('note', note);
    //     formData.append('tech_mail', tech_mail);
    
    //     let files = $('#completionImages')[0].files;
    
    //     for (let i = 0; i < files.length; i++) {
    //         formData.append('images[]', files[i]);
    //     }
    
    //     $.ajax({
    //         url: `/maintenance-requests/${id}/status`,
    //         type: 'POST',
    //         data: formData,
    //         processData: false,
    //         contentType: false,
    
    //         success: function () {
    //             location.reload();
    //         },
    //         error: function(xhr, status, error) {
    //             console.log(xhr);
    //             console.log(status);
    //             console.log(error);
    //             alert('Có lỗi xảy ra: ' + xhr.status);
            
    //         },
    //         complete:function(){
    //             btn.prop('disabled',false);
    //         }
    //     });
    // });

    $(document)
        .off('click', '#confirmChangeStatus')
        .on('click', '#confirmChangeStatus', function () {
            const btn = $(this);
            if (btn.prop('disabled')) return;
            if (selectingImages) return alert('Vui lòng chờ hoàn tất chọn ảnh.');
            upload100Logged = false;

            const id = $('#statusRequestId').val();
            const note = $('#statusNote').val();
            const tech_mail = $('#technicianSelect').val();
            const status = $('#statusSelectWrapper').hasClass('d-none')
                ? $('#newStatus').val()
                : $('#statusSelect').val();
            const images = $('#completionImages')[0].files;

            // Validate waiting confirm needs at least one image
            if (
                status === window.slaStatusCodes.WAITING_CONFIRM &&
                images.length === 0
            ) return alert('Vui lòng tải lên ít nhất 1 ảnh.');

            const MAX_FILE_SIZE = 10 * 1024 * 1024;
            const MAX_TOTAL_SIZE = 45 * 1024 * 1024;
            let totalSize = 0;

            for (const file of images) {
                if (file.size > MAX_FILE_SIZE)
                    return alert(`${file.name} vượt quá 10MB`);
                totalSize += file.size;
            }

            if (totalSize > MAX_TOTAL_SIZE)
                return alert('Tổng dung lượng ảnh vượt quá 45MB.');

            const formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('_method', 'PATCH');
            formData.append('status', status);
            formData.append('note', note);
            formData.append('tech_mail', tech_mail);

            for (const file of images) formData.append('images[]', file);

            btn.prop('disabled', true);

            $.ajax({
                url: `/maintenance-requests/${id}/status`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                timeout: 600000,

                beforeSend() {
                    btn.prop('disabled', true);
                    $('#statusSelect, #completionImages').prop('disabled', true);
                    sendClientLog({
                        type: 'before_send',
                        request_id: id,
                        image_count: images.length
                    });
                    logNetworkInfo('beforeSend');
                },

                xhr() {
                    const xhr = $.ajaxSettings.xhr();
                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', (e) => {
                            if (!e.lengthComputable) return;
                            const percent = Math.round((e.loaded * 100) / e.total);
                            console.log('Upload progress', percent + '%');
                            if (percent === 100 && !upload100Logged) {
                                upload100Logged = true;
                                sendClientLog({
                                    type: 'upload_100',
                                    request_id: id,
                                    total: e.total,
                                    loaded: e.loaded
                                });
                            }
                        });
                    }
                    return xhr;
                },

                success(res) {
                    console.log('=== SUCCESS ===', res);
                    sendClientLog({
                        type: 'success',
                        request_id: id,
                        response: JSON.stringify(res)
                    });
                    logNetworkInfo('success');
                    if (!res.success)
                        return alert('Máy chủ trả về dữ liệu không hợp lệ.');
                    sendClientLog({
                        type: 'success',
                        request_id: id,
                        status: res.sla_status
                    });
                    setTimeout(() => location.reload(), 1000);
                },

                error(xhr, textStatus, errorThrown) {
                    console.group('===== CHANGE STATUS ERROR =====');
                    console.log('HTTP Status:', xhr.status);
                    console.log('Ready State:', xhr.readyState);
                    console.log('Text Status:', textStatus);
                    console.log('Error:', errorThrown);
                    console.log('Online:', navigator.onLine);
                    console.log('Response:', xhr.responseText);
                    console.log('Response JSON:', xhr.responseJSON);
                    console.groupEnd();

                    sendClientLog({
                        type: 'error',
                        request_id: id,
                        status: xhr.status,
                        readyState: xhr.readyState,
                        textStatus,
                        error: errorThrown,
                        response: xhr.responseText,
                        online: navigator.onLine
                    });

                    logNetworkInfo('error');

                    if (textStatus === 'timeout')
                        return alert('Hệ thống xử lý quá lâu hoặc kết nối mạng không ổn định. Vui lòng kiểm tra lại sau.');

                    if (xhr.status === 422) {
                        try {
                            const errors = xhr.responseJSON.errors;
                            const message = Object.values(errors).map(arr => arr[0]).join('\n');
                            alert(message);
                        } catch {
                            alert('Dữ liệu không hợp lệ.');
                        }
                        return;
                    }
                    if (xhr.status === 419)
                        return alert('Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.');

                    if (xhr.status === 0) {
                        if (navigator.onLine) {
                            alert(
                                'Kết nối tới máy chủ bị gián đoạn.\n' +
                                'Yêu cầu có thể đã được xử lý thành công.\n' +
                                'Vui lòng chờ vài giây rồi tải lại trang để kiểm tra.'
                            );
                        } else {
                            alert(
                                'Thiết bị đang mất kết nối Internet.\n' +
                                'Vui lòng kiểm tra mạng rồi thử lại.'
                            );
                        }
                        return;
                    }

                    if (xhr.status >= 500)
                        return alert('Máy chủ đang gặp lỗi. Vui lòng thử lại sau.');

                    alert(
                        `Có lỗi xảy ra.\n\n` +
                        `HTTP: ${xhr.status}\n` +
                        `Text: ${textStatus}`
                    );
                },

                complete(xhr, textStatus) {
                    sendClientLog({
                        type: 'complete',
                        request_id: id,
                        status: xhr.status,
                        readyState: xhr.readyState,
                        textStatus
                    });
                    btn.prop('disabled', false);
                    $('#statusSelect, #completionImages').prop('disabled', false);
                    selectingImages = false;
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

    $(document).on('click', '.btn-detail', function (e) {

        e.preventDefault();
    
        const id = $(this).data('id');
        const modal = new bootstrap.Modal(
            document.getElementById('detailModal')
        );
    
        modal.show();
    
        $.get('/maintenance-requests/' + id + '/detail', function (html) {
            $('#detail-content').html(html);
        });
    });

    $(document).on('click', '.delete-image', function () {
        const button = $(this);
        const imageId = $(this).data('id');

        if (!confirm('Bạn có chắc muốn xóa ảnh này?')) {
            return;
        }

        $.ajax({
            url: `/maintenance-request-images/${imageId}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Loading.hide();
                    button.closest('.img-item').fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Xóa ảnh thất bại');
            },
            complete: function () {
                Loading.hide();
            }
        });

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

    $(document).on('change', '#from-date, #to-date, #from-date-completed, #to-date-completed', function () {
        let fromDate = $('#from-date').val();
        let toDate = $('#to-date').val();
        let fromDateCompleted = $('#from-date-completed').val();
        let toDateCompleted = $('#to-date-completed').val();
        let url = new URL(window.location.href);

        if (fromDate) {
            url.searchParams.set('from-date', fromDate);
        }
        if (toDate) {
            url.searchParams.set('to-date', toDate);
        }
        if (fromDateCompleted) {
            url.searchParams.set('from-date-completed', fromDateCompleted);
        }
        if (toDateCompleted) {
            url.searchParams.set('to-date-completed', toDateCompleted);
        }

        window.location.href = url;
    });

    function toggleFormFields() {
        const modal = $('#createModal');
        const key = modal.find('.issue-selector option:selected').data('key');
        const branch_name_select = $('#branch_name_select').val();

        // Khóa toàn bộ trước
        modal.find('input, textarea')
            .not(
                '.form-branch-name,' +
                '.issue-selector,' +
                '#other_branch_name,' +
                '#other_branch_code,' +
                '#other_branch_email,' +
                '.select2-search__field'
            )
            .prop('readonly', true);

        modal.find('select')
            .not('.form-branch-name, .issue-selector, .severity-field')
            .prop('disabled', true);

        // Bỏ highlight cũ
        modal.find('.editable-highlight')
            .removeClass('editable-highlight');

        // Nếu là OTHER thì mở các trường được phép sửa
        if (key === 'OTHER') {
            // .severity-field,  .processing-time
            modal.find(
                ' .issue-description, .solution-description'
            )
                .prop('readonly', false)
                .prop('disabled', false)
                .addClass('editable-highlight');
        }

        if (branch_name_select == 'other_store') {
            modal.find('select')
            .not('.form-branch-name')
            .prop('disabled', false);
        }
    }

    // Khởi tạo
    toggleFormFields();

    // Khi đổi hạng mục
    $(document).on('change', '#createModal .issue-selector', function () {
        toggleFormFields();
    });

    function toggleFormEditFields() {
        const modal = $('#editModal');
        const key = modal.find('.issue-selector option:selected').data('key');
    
        // Khóa toàn bộ input
        modal.find('input[type=text], input[type=email], input[type=number]')
            .prop('readonly', true);
    
        // Khóa textarea
        modal.find('textarea')
            .prop('readonly', true);
    
        // Bỏ highlight
        modal.find('.editable-highlight')
            .removeClass('editable-highlight');
    
        // Chỉ cho chọn hạng mục
        modal.find('.issue-selector')
            .prop('disabled', false);
    
        // Nếu OTHER
        if (key === 'OTHER') {
            modal.find(
                '.issue-description, .solution-description'
            )
            .prop('readonly', false)
            .prop('disabled', false)
            .addClass('editable-highlight');
        }
    }

    toggleFormEditFields();
    // Khi đổi hạng mục edit
    $(document).on('change', '#editModal .issue-selector', function () {
        toggleFormEditFields();
    });

    $('#editModal').on(
        'change',
        '.issue-selector',
        function () {
            if (isUpdating) return;
            isUpdating = true;

            let $modal = $('#editModal');
            let $selected = $(this).find(':selected');
            fillIssueData($modal, $selected);

            // Check severity data
            let severity = $selected.data('severity');
            if (severity === '1A') {
                // Auto check T7, CN
                $modal.find('#include_saturday').prop('checked', true);
                $modal.find('#include_sunday').prop('checked', true);
            } else {
                $modal.find('#include_saturday').prop('checked', false);
                $modal.find('#include_sunday').prop('checked', false);
            }
            isUpdating = false;
        }
    );

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

    const systemTabs = document.getElementById('systemTabs');
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
                        syncScrollWidthSystem();
                    }
                );
            });
    }

    function syncScrollWidthSystem() {
        let table = $('.tab-pane.active .table-responsive table')[0];
        if (!table) {
            return;
        }
        $('.table-scroll-top-system div').width(
            table.scrollWidth
        );
    }

    syncScrollWidthSystem();

    $(window).on('resize', syncScrollWidth);

    $('.table-scroll-top-system').on('scroll', function () {
        $('.table-responsive').scrollLeft($(this).scrollLeft());
    });

    $('.table-responsive').on('scroll', function () {
        $('.table-scroll-top-system').scrollLeft($(this).scrollLeft());
    });

    $('#exportsModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    $(document).on('submit', '#exportForm', function () {
        $(this).attr(
            'action',
            $('#report_type').val()
        );
    
        bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById('exportsModal')
        )
        .hide();
    
        Loading.show('Đang xuất báo cáo...');
    
        setTimeout(function () {
            Loading.hide();
        }, 3000);
    });

    function toggleTechFilter() {
        const type = $('#report_type option:selected').data('type');
        const showTechFilter = [
            'summary',
            'tech'
        ].includes(type);
    
        $('#tech-filter-section').toggleClass('d-none', !showTechFilter);
    
        if (!showTechFilter) {
            $('#tech-filter-section').find(':checkbox').prop('checked', false);
        }
    }
    
    $('#exportsModal').on('shown.bs.modal', function () {
        toggleTechFilter();
        $(this).find('.select2-branch').select2({
            dropdownParent: $(this),
            width: '100%'
        });
    });

    $('#report_type').on('change', toggleTechFilter);

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
        $('#acceptanceImages').val(null);
    });

    $('#submitAcceptance').on(
        'click',
        function () {
    
            let formData = new FormData();
    
            formData.append(
                '_token',
                $('meta[name="csrf-token"]').attr('content')
            );
    
            formData.append(
                'id',
                $('#acceptance_request_id').val()
            );
    
            formData.append(
                'result',
                $('#acceptance_result').val()
            );
    
            formData.append(
                'note',
                $('#acceptance_note').val()
            );
    
            let files = $('#acceptanceImages')[0].files;
    
            for (let i = 0; i < files.length; i++) {
                formData.append(
                    'images[]',
                    files[i]
                );
            }
    
            $.ajax({
    
                url: '/maintenance-request/acceptance',
    
                type: 'POST',
    
                data: formData,
    
                processData: false,
    
                contentType: false,
    
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

    
    // Tối ưu xử lý lấy calendar info và chọn kỹ thuật viên đặc biệt nếu cần
    window.calendarInfo = null;

    function fetchCalendarInfo() {
        return $.get('/system/calendar-info').done(function(res) {
            window.calendarInfo = res;
        });
    }

    // Đảm bảo là đã có dữ liệu calendarInfo trước khi thao tác
    fetchCalendarInfo();

    $(document).on('change', '#include_saturday, #include_sunday, #include_holiday', function () {
        const modal = $(this).closest('.modal');
        const technicianSelect = modal.find('.form-technician-name');
        const checkedStates = {
            saturday: modal.find('#include_saturday').is(':checked'),
            sunday: modal.find('#include_sunday').is(':checked'),
            holiday: modal.find('#include_holiday').is(':checked')
        };
        const c = window.calendarInfo;

        if (!c) {
            // Nếu chưa có calendarInfo, thử fetch lại và chờ cho lần sau
            fetchCalendarInfo();
            return;
        }

        const shouldUseSpecialTech =
            (checkedStates.saturday && c.is_saturday) ||
            (checkedStates.sunday && c.is_sunday) ||
            (checkedStates.holiday && c.is_holiday);

        if (shouldUseSpecialTech) {
            // Chỉ lưu giá trị trước khi đổi để có thể khôi phục về sau
            if (!technicianSelect.data('previous-value')) {
                technicianSelect.data('previous-value', technicianSelect.val());
            }

            if (window.techNgoaiGio !== undefined) {
                technicianSelect.val(window.techNgoaiGio).trigger('change');
            }
        } else {
            const previousValue = technicianSelect.data('previous-value');
            if (previousValue !== undefined) {
                technicianSelect.val(previousValue).trigger('change');
                technicianSelect.removeData('previous-value');
            }
        }
    });

    // Tự động cập nhật thông tin kỹ thuật viên khi chọn mới
    $('#maintenanceModal').on('change', '.form-technician-name', function () {
        let option = $(this).find(':selected');
        let modal = $('#maintenanceModal');
        modal.find('.technician-mobile').val(option.data('mobile') || '');
        modal.find('.technician-email').val(option.data('email') || '');
    });

    // Hiển thị input nhập "cửa hàng khác" khi chọn tương ứng
    function toggleSystemOtherBranchInput() {
        const selectedValue = $('#branch_name_select').val();
        if (selectedValue === 'other_store') {
            $('#other_store_input_wrap').removeClass('d-none');
        } else {
            $('#other_store_input_wrap').addClass('d-none');
            $('#other_branch_name').val('');
            $('#other_branch_code').val('');
            $('#other_branch_email').val('');
        }
    }

    // Khởi tạo / bind sự kiện khi mở modal (edit/create system)
    $('#maintenanceModal').on('shown.bs.modal', function () {
        $('#branch_name_select').trigger('change');
        $('.form-technician-name').trigger('change');
        initSelect2Modal();
    });

    // Hỗ trợ khôi phục input "cửa hàng khác" khi reload trang
    if ($('#branch_name_select').length) {
        toggleSystemOtherBranchInput();
    }

    // Khi chọn Chi nhánh hệ thống
    $(document).on('change', '#branch_name_select', function () {
        let option = $(this).find(':selected');
        toggleSystemOtherBranchInput();

        let hiddenBranchCodeInput = $('input.form-branch-code');
        let hiddenBranchEmailInput = $('input.form-branch-email');
        if (option.val() === 'other_store') {
            hiddenBranchCodeInput.val('');
            hiddenBranchEmailInput.val('');
        } else {
            hiddenBranchCodeInput.val(option.data('branch_code') || '');
            hiddenBranchEmailInput.val(option.data('branch_email') || '');
        }
    });

    // Tự động cập nhật fields khi chọn sự cố / dịch vụ
    $(document).on('change', '.select2-issue-name', function () {
        let selected = $(this).find(':selected');
        let code = selected.data('code') || '';
        let sla_time = selected.data('sla') || '';
        let issue_description = selected.data('issue_description') || '';
        let modal = $('#maintenanceModal');
        modal.find('.issue-code-system').val(code);
        modal.find('.processing-time-system').val(sla_time);
        modal.find('.issue-description-system').val(issue_description);
    });

    // Khi submit modal, đồng bộ lại các trường branch nếu chọn "cửa hàng khác"
    $(document).on('submit', '#maintenanceModal form', function(e) {
        var $form = $(this);
        var $branchSelect = $form.find('#branch_name_select');
        // Nếu chọn "Cửa hàng khác"
        if ($branchSelect.length && $branchSelect.val() === 'other_store') {
            // Lấy dữ liệu nhập của user ở input đặc biệt
            var branchNameOther = $form.find('[name="other_branch_name"]').val() || '';
            var branchCodeOther = $form.find('[name="other_branch_code"]').val() || '';
            var branchEmailOther = $form.find('[name="other_branch_email"]').val() || '';
            // Gán về cho field ẩn đúng chuẩn backend (branch_code, branch_email)
            $form.find('input.form-branch-code').val(branchCodeOther);
            $form.find('input.form-branch-email').val(branchEmailOther);
            // Đối với branch_name, sẽ tạo input hidden với giá trị tùy ý, xóa option select
            $form.find('select[name="branch_name"]').val('').prop('selected', false);
            if ($form.find('input[name="branch_name"][type="hidden"]').length === 0) {
                $form.append('<input type="hidden" name="branch_name" value="">');
            }
            $form.find('input[name="branch_name"][type="hidden"]').val(branchNameOther);
        } else {
            // Không chọn "cửa hàng khác" thì xóa input hidden nếu có
            $form.find('input[name="branch_name"][type="hidden"]').remove();
        }
    });

    // Tìm kiếm kỹ thuật viên ở danh sách thô (nếu có)
    $(document).on('input', '#system-tech-search', function () {
        const keyword = $(this).val().trim().toLowerCase();
        $('.system-tech-item').each(function () {
            const matched = $(this)
                .text()
                .toLowerCase()
                .includes(keyword);
            $(this).toggleClass('d-none', !matched);
        });
    });

    $('#maintenanceModal').on('hide.bs.modal', function () {
        const $modal = $(this);
        const $form = $modal.find('form');
        if ($form.length) {
            $form[0].reset();
        }
        $modal.find('select').each(function () {
            $(this).val(null).trigger('change.select2');
        });
        const $errorBox = $('#system-form-errors');
        if ($errorBox.length) {
            $errorBox.addClass('d-none').html('');
        }
   
    });

    $(document).on(
        'click',
        '.acceptance-system-btn',
        function () {
    
            $('#acceptance_system_id')
                .val($(this).data('id'));
    
            $('#acceptance_result').val('');
    
            $('#acceptance_note').val('');
    
            $('#acceptance-error')
                .addClass('d-none')
                .html('');
        }
    );

    $('#acceptanceSystemModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    $('#submitAcceptanceSystem').on(
        'click',
        function () {
    
            let formData = new FormData();
    
            formData.append(
                '_token',
                $('meta[name="csrf-token"]').attr('content')
            );
    
            formData.append(
                'id',
                $('#acceptance_system_id').val()
            );
    
            formData.append(
                'result',
                $('#acceptance_result').val()
            );
    
            formData.append(
                'note',
                $('#acceptance_note').val()
            );
    
            $.ajax({
    
                url: '/maintenance-system/acceptance',
    
                type: 'POST',
    
                data: formData,
    
                processData: false,
    
                contentType: false,
    
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

    // Xử lý modal đổi trạng thái cho maintenance system (dùng bởi admin)
    $(document).on('click', '.admin-change-status-system-btn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const id = $btn.data('id');
        const currentStatus = $btn.data('current-status');

        // Hiện modal đổi trạng thái
        const modal = $('#changeStatusSystemModal');

        // set id hiện tại vào modal
        modal.find('#statusRequestId').val(id);

        // set select trạng thái
        let options = '';
        const statusNames = window.slaStatusNames || {};
        Object.entries(statusNames).forEach(([key, value]) => {
            options += `<option value="${key}" ${key === currentStatus ? 'selected' : ''}>${value}</option>`;
        });
        modal.find('#statusSelect').html(options);

        // Hiển thị select trạng thái, ẩn label trạng thái tĩnh
        modal.find('#statusLabel').closest('.mb-3').addClass('d-none');
        modal.find('#statusSelectWrapper').removeClass('d-none');

        // Xóa note cũ
        modal.find('#statusNote').val('');

        // Ẩn delay reason nếu có
        modal.find('#delayReasonGroup').addClass('d-none');
        modal.find('textarea[name="delay_reason"]').val('');

        // Hiện modal
        new bootstrap.Modal(document.getElementById('changeStatusSystemModal')).show();
    });

    // Xử lý khi admin xác nhận đổi trạng thái
    $('#confirmChangeStatusSystem').on('click', function () {
        const modal = $('#changeStatusSystemModal');
        const id = modal.find('#statusRequestId').val();
        const status = modal.find('#statusSelect').val();
        const note = modal.find('#statusNote').val();
        const delayReason = modal.find('textarea[name="delay_reason"]').val() || '';

        // Nếu chuyển sang trạng thái LATED thì yêu cầu nhập lý do trễ
        if (status === 'LATED' && !delayReason.trim()) {
            alert('Vui lòng nhập lý do trễ!');
            modal.find('#delayReasonGroup').removeClass('d-none');
            modal.find('textarea[name="delay_reason"]').focus();
            return;
        }

        // Gửi request
        $.ajax({
            url: '/maintenance-system/change-status/' + id + '/admin',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: status,
                note: note,
                delay_reason: status === 'LATED' ? delayReason : ''
            },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                let msg = xhr.responseJSON?.message ?? 'Có lỗi xảy ra';
                alert(msg);
            }
        });
    });

    // Tự động hiện/tắt ô nhập lý do trễ khi đổi trạng thái
    $(document).on('change', '#statusSelect', function () {
        const status = $(this).val();
        const modal = $('#changeStatusSystemModal');
        if (status === 'LATED') {
            modal.find('#delayReasonGroup').removeClass('d-none');
        } else {
            modal.find('#delayReasonGroup').addClass('d-none');
            modal.find('textarea[name="delay_reason"]').val('');
        }
    });

    // Xử lý modal cập nhật kỹ thuật viên cho maintenance system (dùng bởi admin)
    $(document).on('click', '.admin-update-tech-btn', function (e) {
        e.preventDefault();

        // Lấy thông tin từ data-* attribute của nút bấm
        const $btn = $(this);
        const id = $btn.data('id');
        const name = $btn.data('name') || '';
        const email = $btn.data('email') || '';
        const mobile = $btn.data('mobile') || '';
        const action = $btn.data('action') || '';

        const updateTechModal = $('#updateTechModal');
        const updateTechForm = $('#updateTechForm')[0];
        const errorBox = $('#updateTechError');
        const technicianNameSelect = $('#technician_name');
        const technicianEmailInput = $('#technician_email');
        const technicianMobileInput = $('#technician_mobile');

        // Set action url
        if (action) {
            updateTechForm.action = action;
        } else {
            updateTechForm.action = "/maintenance-system/update-technician-info/" + id;
        }

        // Reset error
        errorBox.addClass('d-none').html('');

        // Reset values
        technicianNameSelect.val(name);
        technicianEmailInput.val(email);
        technicianMobileInput.val(mobile);

        // Nếu select chưa đúng option thì cố set theo text (cho trường hợp rỗng hoặc đặc biệt, fallback)
        if (technicianNameSelect.val() !== name) {
            technicianNameSelect.find('option').each(function() {
                if ($(this).text() === name) {
                    technicianNameSelect.val($(this).val());
                }
            });
        }

        // Show modal
        const bsModal = bootstrap.Modal.getOrCreateInstance(updateTechModal[0]);
        bsModal.show();

        // Gắn lại sự kiện submit cho form (xoá cũ trước để không nhân bản)
        $(updateTechForm).off('submit.updateTech').on('submit.updateTech', function(e) {
            e.preventDefault();
            errorBox.addClass('d-none').html('');
            const formData = new FormData(updateTechForm);

            // Show loading while submitting (using global window.Loading)
            if (window.Loading && typeof window.Loading.show === 'function') {
                window.Loading.show('Đang xử lý...');
            }

            fetch(updateTechForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(async response => {
                if (window.Loading && typeof window.Loading.hide === 'function') {
                    window.Loading.hide();
                }
                const data = await response.json();
                if (!response.ok) throw data;
                bsModal.hide();
                window.dispatchEvent(new Event('tech-updated'));
                location.reload();
            })
            .catch(error => {
                if (window.Loading && typeof window.Loading.hide === 'function') {
                    window.Loading.hide();
                }
                let msg = 'Có lỗi xảy ra.';
                if (error && error.errors) {
                    msg = Object.values(error.errors).flat().join('<br>');
                } else if (error && error.message) {
                    msg = error.message;
                }
                errorBox.html(msg).removeClass('d-none');
            });
        });
    });

    // Xử lý modal cập nhật kỹ thuật viên cho maintenance system (dùng bởi admin) 
    $(document).on('click', '.admin-update-tech-maintenance-btn', function (e) {
        e.preventDefault();

        // Lấy thông tin từ data-* attribute của nút bấm
        const $btn = $(this);
        const id = $btn.data('id');
        const name = $btn.data('name') || '';
        const email = $btn.data('email') || '';
        const mobile = $btn.data('mobile') || '';
        const action = $btn.data('action') || '';

        // Chọn đúng modal và form trên view maintenance
        const updateTechModal = $('#updateTechMaintenanceModal');
        const updateTechForm = $('#updateTechMaintenanceForm')[0];
        const errorBox = $('#updateTechError');
        const technicianNameSelect = $('#technician_name');
        const technicianEmailInput = $('#technician_email');
        const technicianMobileInput = $('#technician_mobile');

        // Setup action cho form, ưu tiên action data truyền vào, fallback url chuẩn
        if (action) {
            updateTechForm.action = action;
        } else {
            updateTechForm.action = "/maintenance-requests/update-technician-info/" + id;
        }

        // Reset error hiển thị
        errorBox.addClass('d-none').html('');

        // Reset các trường input
        technicianNameSelect.val(name);
        technicianEmailInput.val(email);
        technicianMobileInput.val(mobile);

        // Nếu select chưa đúng option thì cố gắng chọn đúng option nhờ text
        if (technicianNameSelect.val() !== name && name) {
            technicianNameSelect.find('option').each(function() {
                if ($(this).text() === name) {
                    technicianNameSelect.val($(this).val());
                }
            });
        }

        // Hiển thị modal
        const bsModal = bootstrap.Modal.getOrCreateInstance(updateTechModal[0]);
        bsModal.show();

        // Chỉ attach 1 lần event submit (xoá cũ nếu trùng)
        $(updateTechForm).off('submit.updateTech').on('submit.updateTech', function (e) {
            e.preventDefault();
            errorBox.addClass('d-none').html('');
            const formData = new FormData(updateTechForm);

            // loading UI gọi hàm window.Loading nếu có
            if (window.Loading && typeof window.Loading.show === 'function') {
                window.Loading.show('Đang xử lý...');
            }

            fetch(updateTechForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(async response => {
                if (window.Loading && typeof window.Loading.hide === 'function') {
                    window.Loading.hide();
                }
                const data = await response.json();
                if (!response.ok) throw data;
                bsModal.hide();
                window.dispatchEvent(new Event('tech-updated'));
                location.reload();
            })
            .catch(error => {
                if (window.Loading && typeof window.Loading.hide === 'function') {
                    window.Loading.hide();
                }
                let msg = 'Có lỗi xảy ra.';
                if (error && error.errors) {
                    msg = Object.values(error.errors).flat().join('<br>');
                } else if (error && error.message) {
                    msg = error.message;
                }
                errorBox.html(msg).removeClass('d-none');
            });
        });
    });
});