import $ from 'jquery';

// ==========================
// Init Common
// ==========================
let initialized = false;

window.selectingImages = false;
window.upload100Logged = false;

export function initCommon() {
    if (initialized) return;
    initialized = true;
    initSelect2();
    initLoading();
    initSelect2Focus();
}

// ==========================
// Global Loading
// ==========================
export function initLoading() {
    window.Loading = {
        show(message = 'Đang xử lý...') {
            $('.loading-message').text(message);
            $('#global-loading').removeClass('d-none');
        },
        hide() {
            $('#global-loading').addClass('d-none');
        },
    };

    $(document)
        .off('ajaxStart ajaxStop')
        .on('ajaxStart', () => window.Loading.show())
        .on('ajaxStop', () => window.Loading.hide());

    $(document)
        .off('submit')
        .on('submit', '.js-loading-form:not(.export-form)', function () {
            window.Loading.show($(this).data('loading-text') || 'Đang xử lý...');
        });
}

// ==========================
// Focus Select2
// ==========================
export function initSelect2Focus() {
    $(document)
        .off('select2:open')
        .on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 0);
        });
}

export function initSelect2() {
    $('.select2-branch, .select2-category , .select2-status')
    .not('#createModal .select2-branch, #createModal .select2-category')
    .select2({
        width: '100%'
    });
}
// ==========================
// Button
// ==========================
export const lockButton = btn => btn?.prop('disabled', true);
export const unlockButton = btn => btn?.prop('disabled', false);

// ==========================
// Reload
// ==========================
export function reloadPage(delay = 500) {
    setTimeout(() => location.reload(), delay);
}

// ==========================
// Client Log
// ==========================
export function sendClientLog(data = {}) {
    $.post({
        url: '/client-log',
        timeout: 3000,
        data: {
            _token: csrf(),
            ...data
        }
    });
}

// ==========================
// Network Log
// ==========================
export function logNetworkInfo(step) {
    const nav = performance.getEntriesByType('navigation')[0] || {};
    sendClientLog({
        type: 'network',
        step,
        time: new Date().toISOString(),
        online: navigator.onLine,
        connectionType: navigator.connection?.effectiveType ?? null,
        downlink: navigator.connection?.downlink ?? null,
        rtt: navigator.connection?.rtt ?? null,
        pageLoad: nav.duration ?? null,
        userAgent: navigator.userAgent
    });
}

// ==========================
// Validate Images
// ==========================
export function validateImages(files, errorBox = null, maxFile = 10, maxTotal = 45) {
    const MAX_FILE = maxFile * 1024 * 1024;
    const MAX_TOTAL = maxTotal * 1024 * 1024;
    let total = 0;

    for (const file of files) {
        if (file.size > MAX_FILE) {
            if (errorBox) {
                errorBox.innerHTML = `${file.name} vượt quá ${maxFile}MB`;
                errorBox.classList.remove('d-none');
            }
            return false;
        }
        total += file.size;
    }
    if (total > MAX_TOTAL) {
        if (errorBox) {
            errorBox.innerHTML = `Tổng dung lượng ảnh vượt quá ${maxTotal}MB`;
            errorBox.classList.remove('d-none');
        }
        return false;
    }
    return true;
}

// ==========================
// Upload Progress
// ==========================
export function createUploadXHR(callback = null) {
    const xhr = $.ajaxSettings.xhr();
    if (xhr.upload) {
        xhr.upload.addEventListener(
            'progress',
            e => {
                if (e.lengthComputable && callback)
                    callback(Math.round((e.loaded * 100) / e.total), e);
            },
            false
        );
    }
    return xhr;
}

// ==========================
// Ajax Error Handler
// ==========================
export function handleAjaxError(xhr, textStatus) {
    if (textStatus === 'timeout') {
        alert('Hệ thống xử lý quá lâu hoặc kết nối mạng không ổn định.');
        return;
    }
    if (xhr.status === 422) {
        try {
            const errors = xhr.responseJSON.errors;
            alert(Object.values(errors).map(arr => arr[0]).join('\n'));
        } catch {
            alert('Dữ liệu không hợp lệ.');
        }
        return;
    }
    if (xhr.status === 419) {
        alert('Phiên đăng nhập đã hết hạn.');
        return;
    }
    if (xhr.status === 0) {
        if (navigator.onLine) {
            alert(
                'Kết nối tới máy chủ bị gián đoạn.\n' +
                'Yêu cầu có thể đã được xử lý thành công.\n' +
                'Vui lòng chờ vài giây rồi tải lại trang để kiểm tra.'
            );
        } else {
            alert('Thiết bị đang mất kết nối Internet.\nVui lòng kiểm tra mạng rồi thử lại.');
        }
        return;
    }
    if (xhr.status >= 500) {
        alert('Máy chủ đang gặp lỗi. Vui lòng thử lại sau.');
        return;
    }
    alert(`Có lỗi xảy ra.\nHTTP: ${xhr.status}\n${textStatus}`);
}

// ==========================
// Append Files to FormData
// ==========================
export function appendFilesToFormData(formData, files, field = 'images[]') {
    if (!files?.length) return;
    formData.delete(field);
    for (const file of files) {
        formData.append(field, file);
    }
}

// ==========================
// Lấy csrf-token
// ==========================
export function csrf() {
    return $('meta[name="csrf-token"]').attr('content');
}

// ==========================
// Build FormData từ object
// ==========================
export function buildFormData(data = {}) {
    const formData = new FormData();
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value ?? '');
    }
    return formData;
}

// ==========================
// Show/Hide Error
// ==========================
export function showError(errorBox, message) {
    if (!errorBox) return;
    errorBox.innerHTML = message;
    errorBox.classList.remove('d-none');
}

export function hideError(errorBox) {
    if (!errorBox) return;
    errorBox.innerHTML = '';
    errorBox.classList.add('d-none');
}

// ==========================
// Xóa giá trị input file
// ==========================
export function clearFileInput(selector) {
    $(selector).val(null);
}

// ==========================
// Log Ajax
// ==========================
export function logAjax(type, id, data = {}) {
    sendClientLog({
        type,
        request_id: id,
        ...data
    });
}

// ==========================
// Ajax Json Helper
// ==========================
export function ajaxJson(options) {
    return $.ajax({
        cache: false,
        timeout: 600000,
        processData: false,
        contentType: false,
        ...options
    });
}

// Xử lý ảnh khi change-status
export function initStatusSubmit(options) {
    const defaultConfig = {
        formSelector: '',
        imageSelector: '',
        errorSelector: '',
        submitSelector: '',
        waitingStatus: window.slaStatusCodes?.WAITING_CONFIRM,
        success: () => location.reload(),
    };
    const config = { ...defaultConfig, ...options };
    const form = document.querySelector(config.formSelector);
    if (!form) return;

    const errorBox = document.querySelector(config.errorSelector);
    const imageInput = document.querySelector(config.imageSelector);
    const submitBtn = document.querySelector(config.submitSelector);

    $(form).off('submit.status').on('submit.status', async function (e) {
        e.preventDefault();
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.innerHTML = '';
        }
        lockButton($(submitBtn));

        // Hiển thị loading khi submit
        if (window.Loading?.show) window.Loading.show('Đang xử lý...');

        // Chuẩn hóa image append
        const formData = new FormData(form);
        if (imageInput?.files?.length) {
            formData.delete('images[]');
            Array.from(imageInput.files).forEach(file =>
                formData.append('images[]', file)
            );
        }

        // Validate ảnh
        if (imageInput?.files?.length && !validateImages(imageInput.files, errorBox)) {
            unlockButton($(submitBtn));
            if (window.Loading?.hide) window.Loading.hide();
            return;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            if (window.Loading?.hide) window.Loading.hide();
            if (response.ok) {
                config.success(await response.json());
                return;
            }
            // Xử lý lỗi backend trả về
            let msg = 'Có lỗi xảy ra.';
            if (response.status === 422) {
                try {
                    const json = await response.json();
                    msg = Object.values(json.errors || {}).flat().join('<br>');
                } catch { }
            } else if (response.status === 403) {
                msg = 'Bạn không có quyền thực hiện!';
            } else if (response.status === 419) {
                msg = 'Phiên đăng nhập đã hết hạn.';
            } else if (response.status >= 500) {
                msg = 'Máy chủ đang gặp lỗi.';
            }
            if (errorBox) {
                errorBox.innerHTML = msg;
                errorBox.classList.remove('d-none');
            }
        } catch {
            if (window.Loading?.hide) window.Loading.hide();
            if (errorBox) {
                errorBox.innerHTML = 'Không thể gửi yêu cầu.';
                errorBox.classList.remove('d-none');
            }
        } finally {
            unlockButton($(submitBtn));
        }
    });
};