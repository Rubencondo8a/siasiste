var AdminModals = (function() {
    var baseUrl = window.BASE_URL || '';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $(document).on('click', '.toggle-password', function(e) {
        e.preventDefault();
        var input = $(this).closest('.input-group').find('input');
        var isPassword = input.attr('type') === 'password';
        input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    function getCsrfToken() {
        var token = $('meta[name="csrf-token"]').attr('content');
        return token || csrfToken;
    }

    function openModal(url, title, size) {
        var modal = $('#adminModal');
        var modalDialog = modal.find('.modal-dialog');
        modalDialog.removeClass('modal-sm modal-lg modal-xl');
        if (size) modalDialog.addClass('modal-' + size);
        modal.find('#adminModalLabel').text(title);
        modal.find('#adminModalBody').html(
            '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-info"></i><p class="mt-2">Cargando...</p></div>'
        );
        modal.find('#adminModalFooter').hide().html('');
        modalDialog.css({ top: 0, left: 0, position: 'relative', width: '' });
        modal.modal('show');

        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html) {
                modal.find('#adminModalBody').html(html);
                initFormSubmit(modal);
            },
            error: function() {
                modal.find('#adminModalBody').html(
                    '<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-2x text-danger"></i><p class="mt-2">Error al cargar el contenido.</p></div>'
                );
            }
        });
    }

    function initFormSubmit(modal) {
        modal.find('form[data-ajax="true"]').off('submit').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            var hasFileInput = form.find('input[type="file"]').length > 0;
            var ajaxData;
            if (hasFileInput) {
                ajaxData = new FormData(form[0]);
                if (!form.find('input[name="_csrf_token"]').length) {
                    ajaxData.append('_csrf_token', getCsrfToken());
                }
            } else {
                ajaxData = form.serialize();
                if (ajaxData.indexOf('_csrf_token=') === -1) {
                    ajaxData += '&_csrf_token=' + encodeURIComponent(getCsrfToken());
                }
            }

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: ajaxData,
                processData: hasFileInput ? false : true,
                contentType: hasFileInput ? false : 'application/x-www-form-urlencoded',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    if (typeof response === 'object') {
                        if (response.success) {
                            modal.modal('hide');
                            showToast('success', response.message);
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            modal.find('#adminModalBody').html(response.html || response.message);
                            initFormSubmit(modal);
                        }
                    } else {
                        modal.modal('hide');
                        showToast('success', 'Operación exitosa.');
                        setTimeout(function() { location.reload(); }, 800);
                    }
                },
                error: function(xhr) {
                    var resp = xhr.responseJSON;
                    if (resp && resp.message) {
                        modal.find('#adminModalBody').html(
                            '<div class="alert alert-danger">' + resp.message + '</div>' + (resp.html || '')
                        );
                    } else {
                        modal.find('#adminModalBody').html(
                            '<div class="alert alert-danger">Error al guardar. Intente de nuevo.</div>'
                        );
                    }
                    btn.prop('disabled', false).html(originalHtml);
                    initFormSubmit(modal);
                }
            });
        });
    }

    function showToast(type, message) {
        Swal.fire({
            icon: type,
            text: message,
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    }

    function confirmDelete(url, message) {
        Swal.fire({
            title: '¿Está seguro?',
            text: message || 'No podrá revertir esta acción.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            showToast('error', response.message);
                        }
                    },
                    error: function() {
                        showToast('error', 'Error al eliminar. Intente de nuevo.');
                    }
                });
            }
        });
    }

    function resetPassword(url, username) {
        Swal.fire({
            title: 'Restablecer contraseña',
            html: 'Se restablecerá la contraseña de <strong>' + username + '</strong>.<br><br>' +
                  'La <strong>contraseña temporal será:</strong><br>' +
                  '<div style="font-size:1.6rem;font-weight:700;letter-spacing:2px;color:#007bff;margin:8px 0;">12345678</div>' +
                  'El usuario tendrá <strong>30 minutos</strong> para cambiarla,<br>de lo contrario deberá solicitar un nuevo restablecimiento.<br><br>¿Continuar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-key"></i> Restablecer',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Contraseña restablecida',
                                html: response.message,
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Aceptar'
                            });
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast('error', response.message);
                        }
                    },
                    error: function() {
                        showToast('error', 'Error al restablecer. Intente de nuevo.');
                    }
                });
            }
        });
    }

    function openForm(controller, action, id, title, size) {
        var url = baseUrl + controller + '/' + action;
        if (id) url += '?id=' + id;
        openModal(url, title, size || 'lg');
    }

    function openDetail(controller, action, id, title) {
        var url = baseUrl + controller + '/' + action + '?id=' + id;
        openModal(url, title, 'lg');
    }

    function confirmAction(url, message) {
        Swal.fire({
            title: '¿Está seguro?',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            showToast('error', response.message);
                        }
                    },
                    error: function() {
                        showToast('error', 'Error de conexión. Intente de nuevo.');
                    }
                });
            }
        });
    }

    return {
        openModal: openModal,
        openForm: openForm,
        openDetail: openDetail,
        confirmDelete: confirmDelete,
        confirmAction: confirmAction,
        resetPassword: resetPassword,
        showToast: showToast
    };
})();