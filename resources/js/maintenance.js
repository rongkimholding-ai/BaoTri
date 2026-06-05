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

                    if (res.confirmed == 'true') {
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
});