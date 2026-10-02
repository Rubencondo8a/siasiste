</div>

<footer class="main-footer">
    <strong>&copy; <?= date('Y') ?> Sigametec.</strong>
    Todos los derechos reservados.
    <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 3.0
    </div>
</footer>
</div>

<!-- jQuery se carga en header.php para disponibilidad en scripts de vistas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/3.0.2/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/3.0.2/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/4.0.2/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/4.0.2/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>js/app.js?v=<?= filemtime(ROOT_PATH . 'public/js/app.js') ?>"></script>
<script>
$(document).ready(function() {
    $('table.table').not('.nodt').each(function() {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "No hay registros disponibles",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    zeroRecords: "No se encontraron resultados",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                pageLength: 20,
                lengthMenu: [10, 20, 30, 50, 100],
                order: [],
                responsive: true
            });
        }
    });
});
</script>
<script>
$(document).ajaxError(function(event, jqXHR) {
    if (jqXHR.status === 401 || jqXHR.status === 302 ||
        (typeof jqXHR.responseText === 'string' && jqXHR.responseText.length > 0 && (jqXHR.responseText.substring(0, 15).indexOf('<!DOCTYPE') !== -1 || jqXHR.responseText.substring(0, 6).indexOf('<html') !== -1))) {
        window.location.href = window.BASE_URL + "auth/login";
    }
});
$(document).ready(function() {
    $(document).on("click", "#btnLogout", function(e) {
        e.preventDefault();
        Swal.fire({
            title: "¿Cerrar sesión?",
            text: "Está a punto de salir del sistema.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Salir',
            cancelButtonText: "Cancelar"
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = window.BASE_URL + "auth/cerrarSesion";
            }
        });
    });
});
</script>
<script>
(function() {
    if (typeof window.SESSION_TIMEOUT !== 'number' || window.SESSION_TIMEOUT <= 0) {
        return;
    }
    var idleTime = 0;
    var timeoutMs = window.SESSION_TIMEOUT * 1000;
    var redirected = false;
    var loggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

    if (!loggedIn) {
        return;
    }

    function resetIdle() {
        idleTime = 0;
    }

    function autoLogout() {
        if (redirected) {
            return;
        }
        redirected = true;
        window.location.href = window.BASE_URL + "auth/cerrarSesion";
    }

    var events = ['click', 'keypress', 'keydown', 'mousemove', 'scroll', 'touchstart'];
    for (var i = 0; i < events.length; i++) {
        document.addEventListener(events[i], resetIdle, true);
    }

    setInterval(function() {
        idleTime += 1000;
        if (idleTime >= timeoutMs) {
            autoLogout();
        }
    }, 1000);
})();
</script>
<?php require_once __DIR__ . '/../partials/unsaved_modal.php'; ?>
<?php if (!empty($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($inlineScripts)): ?>
    <script>
    <?php foreach ($inlineScripts as $script): ?>
        <?= $script ?>
    <?php endforeach; ?>
    </script>
<?php endif; ?>
</body>
</html>
