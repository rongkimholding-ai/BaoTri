import $ from 'jquery';

import {
    initCommon,
    reloadPage,
    csrf,
    sendClientLog,
    initStatusSubmit,
} from './common';

$(function () {

    initCommon();

    window.initSystemStatusModal = function () {
        initStatusSubmit({
            formSelector: '#maintenanceModalContent form',
            imageSelector: '#completionSystemImages',
            submitSelector: '#confirmSystemChangeStatus',
            errorSelector: '#system-form-errors',
            success: reloadPage
        });
    };

    // Khởi tạo Select2 trong modal (edit/create issue)
    function initSelect2Modal() {
        const $modal = $('#maintenanceModal');
        $modal.find('.select2-issue-name, .select2-branch-system, .select2-system-tech').each(function () {
            $(this).select2({ width: '100%', dropdownParent: $modal });
        });
    }

    // Tự động trigger change cho branch nếu có
    const $branchName = $('.form-branch-name');
    if ($branchName.length && $branchName.val()) $branchName.trigger('change');

    // Cập nhật inline chỉ tiêu (target system)
    $(document).on('change', '.inline-target-system', function () {
        const row = $(this).closest('tr');
        $.ajax({
            url: '/reports/technician-system-update',
            type: 'POST',
            data: {
                _token: csrf(),
                technician_name: $(this).data('tech'),
                store_count: row.find('[data-field="store_count"]').val(),
                daily_target: row.find('[data-field="daily_target"]').val(),
                monthly_target: row.find('[data-field="monthly_target"]').val()
            },
            success: () => console.log('saved')
        });
    });

    // Tab hệ thống: lưu/restore tab active vào sessionStorage
    const systemTabs = document.getElementById('systemTabs');
    if (systemTabs) {
        const STORAGE_KEY = 'active_tab_' + window.location.pathname;
        // Khôi phục tab
        const savedTab = sessionStorage.getItem(STORAGE_KEY);
        if (savedTab) {
            const tabButton = systemTabs.querySelector(
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
        systemTabs
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

    $(window).on('resize', syncScrollWidthSystem());

    $('.table-scroll-top-system').on('scroll', function () {
        $('.table-responsive').scrollLeft($(this).scrollLeft());
    });

    $('.table-responsive').on('scroll', function () {
        $('.table-scroll-top-system').scrollLeft($(this).scrollLeft());
    });

    // Reset export modal khi đóng
    $('#exportsSystemModal').on('hidden.bs.modal', function () {
        this.querySelector('form')?.reset();
    });

    // Xử lý submit export report
    $(document).on('submit', '#exportSystemForm', function () {
        $(this).attr('action', $('#report_type_system').val());
        bootstrap.Modal.getOrCreateInstance(document.getElementById('exportsSystemModal')).hide();
        window.Loading.show('Đang xuất báo cáo...');
        setTimeout(() => window.Loading.hide(), 3000);
    });

    // Toggle filter kỹ thuật viên hệ thống trong export
    function toggleTechSystemFilter() {
        const type = $('#report_type_system option:selected').data('type');
        const showTechFilter = ['summary', 'tech'].includes(type);
        $('#tech-filter-system-section').toggleClass('d-none', !showTechFilter);
        if (!showTechFilter) $('#tech-filter-system-section').find(':checkbox').prop('checked', false);
    }
    $('#exportsSystemModal').on('shown.bs.modal', function () {
        toggleTechSystemFilter();
        $(this).find('.select2-branch').select2({
            dropdownParent: $(this),
            width: '100%'
        });
    });
    $('#report_type_system').on('change', toggleTechSystemFilter);

    // Lấy calendar info
    window.calendarInfo = null;
    function fetchCalendarInfo() {
        return $.get('/system/calendar-info').done(res => window.calendarInfo = res);
    }
    fetchCalendarInfo();

    // Tối ưu chọn kỹ thuật viên đặc biệt khi chọn thêm thứ 7, CN, lễ
    $(document).on('change', '#include_saturday, #include_sunday, #include_holiday', function () {
        const modal = $(this).closest('.modal');
        const technicianSelect = modal.find('.form-technician-name');
        const checked = {
            saturday: modal.find('#include_saturday').is(':checked'),
            sunday: modal.find('#include_sunday').is(':checked'),
            holiday: modal.find('#include_holiday').is(':checked')
        };
        const c = window.calendarInfo;
        if (!window.calendarInfo) {
            fetchCalendarInfo().done(() => {
                $(this).trigger('change');
            });
            return;
        }
        const ngoaiGio = (checked.saturday && c.is_saturday) || (checked.sunday && c.is_sunday) || (checked.holiday && c.is_holiday);
        if (ngoaiGio) {
            if (!technicianSelect.data('previous-value')) technicianSelect.data('previous-value', technicianSelect.val());
            if (window.techNgoaiGio !== undefined) technicianSelect.val(window.techNgoaiGio).trigger('change');
        } else {
            const previous = technicianSelect.data('previous-value');
            if (previous !== undefined) {
                technicianSelect.val(previous).trigger('change');
                technicianSelect.removeData('previous-value');
            }
        }
    });

    // Tự cập nhật info kỹ thuật viên khi chọn
    $('#maintenanceModal').on('change', '.form-technician-name', function () {
        const option = $(this).find(':selected');
        const modal = $('#maintenanceModal');
        modal.find('.technician-mobile').val(option.data('mobile') || '');
        modal.find('.technician-email').val(option.data('email') || '');
    });

    // Hiển thị ô nhập cửa hàng khác khi chọn option tương ứng
    function toggleSystemOtherBranchInput() {
        const val = $('#branch_name_select').val();
        if (val === 'other_store') {
            $('#other_store_input_wrap').removeClass('d-none');
        } else {
            $('#other_store_input_wrap').addClass('d-none');
            $('#other_branch_name, #other_branch_code, #other_branch_email').val('');
        }
    }
    // Khởi tạo/bind khi mở modal
    $('#maintenanceModal').on('shown.bs.modal', function () {
        $('#branch_name_select').trigger('change');
        $('.form-technician-name').trigger('change');
        initSelect2Modal();
    });
    if ($('#branch_name_select').length) toggleSystemOtherBranchInput();

    // Khi chọn branch hệ thống
    $(document).on('change', '#branch_name_select', function () {
        const option = $(this).find(':selected');
        toggleSystemOtherBranchInput();
        const hiddenCode = $('input.form-branch-code');
        const hiddenEmail = $('input.form-branch-email');
        if (option.val() === 'other_store') {
            hiddenCode.val('');
            hiddenEmail.val('');
        } else {
            hiddenCode.val(option.data('branch_code') || '');
            hiddenEmail.val(option.data('branch_email') || '');
        }
    });

    // Khi chọn issue/service thì cập nhật các field liên quan
    $(document).on('change', '.select2-issue-name', function () {
        const selected = $(this).find(':selected');
        const modal = $('#maintenanceModal');
        modal.find('.issue-code-system').val(selected.data('code') || '');
        modal.find('.processing-time-system').val(selected.data('sla') || '');
        modal.find('.issue-description-system').val(selected.data('issue_description') || '');
    });

    // Đồng bộ branch khi submit, nếu là cửa hàng khác sẽ insert input hidden
    $(document).on('submit', '#maintenanceModal form', function (e) {
        const $form = $(this);
        const $branchSelect = $form.find('#branch_name_select');
        if ($branchSelect.length && $branchSelect.val() === 'other_store') {
            const nameOther = $form.find('[name="other_branch_name"]').val() || '';
            const codeOther = $form.find('[name="other_branch_code"]').val() || '';
            const emailOther = $form.find('[name="other_branch_email"]').val() || '';
            $form.find('input.form-branch-code').val(codeOther);
            $form.find('input.form-branch-email').val(emailOther);
            $form.find('select[name="branch_name"]').val('').prop('selected', false);
            if ($form.find('input[name="branch_name"][type="hidden"]').length === 0) {
                $form.append('<input type="hidden" name="branch_name" value="">');
            }
            $form.find('input[name="branch_name"][type="hidden"]').val(nameOther);
        } else {
            $form.find('input[name="branch_name"][type="hidden"]').remove();
        }
    });

    // Tìm kiếm kỹ thuật viên realtime
    $(document).on('input', '#system-tech-search', function () {
        const keyword = $(this).val().trim().toLowerCase();
        $('.system-tech-item').each(function () {
            $(this).toggleClass('d-none', !$(this).text().toLowerCase().includes(keyword));
        });
    });

    // Khi đã chọn xong ảnh: enable nút, log số lượng/size ảnh
    $(document).on('change', '#completionSystemImages', function () {
        window.selectingImages = false;
        $('#completionSystemImages').prop('disabled', false);
        const files = this.files;
        sendClientLog({
            type: 'image_selected',
            image_count: files.length,
            total_size: Array.from(files).reduce((t, f) => t + f.size, 0)
        });
    });

    // Reset modal dữ liệu khi ẩn modal chính
    $('#maintenanceModal').on('hide.bs.modal', function () {
        const $modal = $(this);
        const $form = $modal.find('form');
        if ($form.length) $form[0].reset();
        $modal.find('select').each(function () {
            $(this).val(null).trigger('change.select2');
        });
        $('#system-form-errors').addClass('d-none').html('');
        $('#completionSystemImages').val(null);
        $('#imageSystemUploadWrapper').addClass('d-none');
        window.selectingImages = false;
        window.upload100Logged = false;
    });

    // Chuẩn bị xác nhận nghiệm thu
    $(document).on('click', '.acceptance-system-btn', function () {
        $('#acceptance_system_id').val($(this).data('id'));
        $('#acceptance_result, #acceptance_note').val('');
        $('#acceptance-error').addClass('d-none').html('');
    });

    $('#acceptanceSystemModal').on('hidden.bs.modal', function () {
        this.querySelector('form')?.reset();
    });

    // Submit nghiệm thu
    $('#submitAcceptanceSystem').on('click', function () {
        const formData = new FormData();
        formData.append('_token', csrf());
        formData.append('id', $('#acceptance_system_id').val());
        formData.append('result', $('#acceptance_result').val());
        formData.append('note', $('#acceptance_note').val());
        $.ajax({
            url: '/maintenance-system/acceptance',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) reloadPage();
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'Có lỗi xảy ra';
                $('#acceptance-error').removeClass('d-none').html(msg);
            }
        });
    });

    // Đổi trạng thái request - admin
    $(document).on('click', '.admin-change-status-system-btn', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const id = $btn.data('id');
        const currentStatus = $btn.data('current-status');
        const modal = $('#changeStatusSystemModal');
        let options = '';
        const statusNames = window.slaStatusNames || {};
        Object.entries(statusNames).forEach(([key, value]) => {
            options += `<option value="${key}" ${key === currentStatus ? 'selected' : ''}>${value}</option>`;
        });
        modal.find('#statusRequestId').val(id);
        modal.find('#statusSelect').html(options);
        modal.find('#statusLabel').closest('.mb-3').addClass('d-none');
        modal.find('#statusSelectWrapper').removeClass('d-none');
        modal.find('#statusNote').val('');
        modal.find('#delayReasonGroup').addClass('d-none');
        modal.find('textarea[name="delay_reason"]').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('changeStatusSystemModal')).show();
    });

    // Xác nhận đổi trạng thái
    $('#confirmChangeStatusSystem').on('click', function () {
        const modal = $('#changeStatusSystemModal');
        const id = modal.find('#statusRequestId').val();
        const status = modal.find('#statusSelect').val();
        const note = modal.find('#statusNote').val();
        const delayReason = modal.find('textarea[name="delay_reason"]').val() || '';
        if (status === 'LATED' && !delayReason.trim()) {
            alert('Vui lòng nhập lý do trễ!');
            modal.find('#delayReasonGroup').removeClass('d-none');
            modal.find('textarea[name="delay_reason"]').focus();
            return;
        }
        $.ajax({
            url: '/maintenance-system/change-status/' + id + '/admin',
            type: 'POST',
            data: {
                _token: csrf(),
                status: status,
                note: note,
                delay_reason: status === 'LATED' ? delayReason : ''
            },
            success: function () { reloadPage(); },
            error: function (xhr) {
                alert(xhr.responseJSON?.message ?? 'Có lỗi xảy ra');
            }
        });
    });

    // Hiện/tắt nhập lý do trễ theo trạng thái
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

    // Xử lý update kỹ thuật viên (admin - modal): SYSTEM version
    $(document).on('click', '.admin-update-tech-btn', function (e) {
        e.preventDefault();
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

        updateTechForm.action = action ? action : "/maintenance-system/update-technician-info/" + id;
        errorBox.addClass('d-none').html('');
        technicianNameSelect.val(name);
        technicianEmailInput.val(email);
        technicianMobileInput.val(mobile);
        // fallback chọn tên kỹ thuật viên theo text
        if (technicianNameSelect.val() !== name) {
            technicianNameSelect.find('option').each(function () {
                if ($(this).text() === name)
                    technicianNameSelect.val($(this).val());
            });
        }

        const bsModal = bootstrap.Modal.getOrCreateInstance(updateTechModal[0]);
        bsModal.show();

        $(updateTechForm).off('submit.updateTech').on('submit.updateTech', function (e) {
            e.preventDefault();
            errorBox.addClass('d-none').html('');
            const formData = new FormData(updateTechForm);
            window.Loading?.show && window.Loading.show('Đang xử lý...');
            fetch(updateTechForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                },
                body: formData
            })
                .then(async response => {
                    window.Loading?.hide && window.Loading.hide();
                    const result = await response.json();
                    if (!response.ok) throw result;
                    bsModal.hide();
                    window.dispatchEvent(new Event('tech-updated'));
                    reloadPage();
                })
                .catch(error => {
                    window.Loading?.hide && window.Loading.hide();
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

    // Xử lý update kỹ thuật viên (admin - modal): MAINTENANCE version
    $(document).on('click', '.admin-update-tech-maintenance-btn', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const id = $btn.data('id');
        const name = $btn.data('name') || '';
        const email = $btn.data('email') || '';
        const mobile = $btn.data('mobile') || '';
        const action = $btn.data('action') || '';
        const updateTechModal = $('#updateTechMaintenanceModal');
        const updateTechForm = $('#updateTechMaintenanceForm')[0];
        const errorBox = $('#updateTechError');
        const technicianNameSelect = $('#technician_name');
        const technicianEmailInput = $('#technician_email');
        const technicianMobileInput = $('#technician_mobile');

        updateTechForm.action = action ? action : "/maintenance-requests/update-technician-info/" + id;
        errorBox.addClass('d-none').html('');
        technicianNameSelect.val(name);
        technicianEmailInput.val(email);
        technicianMobileInput.val(mobile);
        if (technicianNameSelect.val() !== name && name) {
            technicianNameSelect.find('option').each(function () {
                if ($(this).text() === name) technicianNameSelect.val($(this).val());
            });
        }
        const bsModal = bootstrap.Modal.getOrCreateInstance(updateTechModal[0]);
        bsModal.show();

        $(updateTechForm).off('submit.updateTech').on('submit.updateTech', function (e) {
            e.preventDefault();
            errorBox.addClass('d-none').html('');
            const formData = new FormData(updateTechForm);
            window.Loading?.show && window.Loading.show('Đang xử lý...');
            fetch(updateTechForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                },
                body: formData
            })
                .then(async response => {
                    window.Loading?.hide && window.Loading.hide();
                    const data = await response.json();
                    if (!response.ok) throw data;
                    bsModal.hide();
                    window.dispatchEvent(new Event('tech-updated'));
                    reloadPage();
                })
                .catch(error => {
                    window.Loading?.hide && window.Loading.hide();
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