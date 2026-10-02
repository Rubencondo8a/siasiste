<?php $pageTitle = $pageTitle ?? 'Motivos de Puntos'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-tags mr-2"></i>Motivos de Puntos</h1>
        <div class="d-flex">
            <a href="<?= BASE_URL ?>docente/registrarPuntos<?= !empty($cursoRegreso) ? '?curso_id=' . (int)$cursoRegreso : '' ?>" class="btn btn-outline-secondary mr-2">
                <i class="fas fa-arrow-left"></i> Volver a Participación
            </a>
            <button type="button" class="btn btn-primary" onclick="Motivos.nuevo()">
                <i class="fas fa-plus"></i> Nuevo motivo
            </button>
        </div>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-body table-responsive">
            <table id="tablaMotivos" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:45px;">#</th>
                        <th>Motivo / Descripción</th>
                        <th style="width:190px;">Categoría</th>
                        <th style="width:100px;">Origen</th>
                        <th style="width:100px;">Estado</th>
                        <th style="width:180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($motivos as $m): ?>
                    <?php
                        $sistema = (int) $m['docente_id'] === 0;
                        $activo = (int) $m['activo'] === 1;
                    ?>
                    <tr data-id="<?= (int) $m['id'] ?>">
                        <td><?= (int) $m['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($m['nombre']) ?></strong>
                            <?php if (!empty($m['descripcion'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($m['descripcion']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($m['categoria'])): ?>
                                <span class="badge badge-primary"><?= htmlspecialchars(($m['categoria_icono'] ? $m['categoria_icono'] . ' ' : '') . $m['categoria']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $sistema ? '<span class="badge badge-info">sistema</span>' : '<span class="badge badge-light">propio</span>' ?></td>
                        <td><?= $activo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>' ?></td>
                        <td>
                            <?php if (!$sistema): ?>
                                <a href="#" class="btn btn-sm btn-outline-primary" title="Editar"
                                   onclick="Motivos.editar(<?= (int)$m['id'] ?>); return false;"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-sm btn-<?= $activo ? 'outline-danger' : 'outline-success' ?>" onclick="Motivos.alternar(<?= (int)$m['id'] ?>)">
                                    <?= $activo ? '<i class="fas fa-ban"></i> Desactivar' : '<i class="fas fa-check"></i> Activar' ?>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($motivos)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sin motivos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="alert alert-info py-2 small">
        <i class="fas fa-info-circle mr-1"></i>
        Los motivos marcados como <span class="badge badge-info">sistema</span> son usados automáticamente por el sistema (ej.: puntos por likes en debates) y no pueden modificarse. Al desactivar un motivo propio deja de aparecer al calificar, pero el historial anterior se conserva.
    </div>
</section>

<form id="frmAlternarMotivo" method="post" action="<?= BASE_URL ?>docente/alternarMotivoPunto" class="d-none">
    <?= csrfField() ?>
    <input type="hidden" name="id" id="alternar_motivo_id">
</form>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = $extraScripts ?? [];
$extraScripts[] = BASE_URL . 'js/admin_modals.js';
$inlineScripts = $inlineScripts ?? [];
$inlineScripts[] = "
\$(document).ready(function() {
    var tabla = \$('#tablaMotivos').DataTable();
    if (tabla) { tabla.destroy(); \$('#tablaMotivos').removeClass('dtr-hidden'); }
    \$('#tablaMotivos').DataTable({
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_',
            infoEmpty: 'Sin motivos registrados',
            zeroRecords: 'Sin resultados',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        },
        lengthMenu: [10, 15, 20, 50, 100],
        pageLength: 10,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: [5] }]
    });
});
";
?>
<script>
const Motivos = {
    csrf: () => document.querySelector('meta[name="csrf-token"]').content,
    nuevo() {
        AdminModals.openModal('<?= BASE_URL ?>docente/motivoFormulario', 'Nuevo Motivo', 'md');
    },
    editar(id) {
        AdminModals.openModal('<?= BASE_URL ?>docente/motivoFormulario?id=' + id, 'Editar Motivo', 'md');
    },
    guardar(ev) {
        ev.preventDefault();
        const fd = new FormData(ev.target);
        if (!fd.has('_csrf_token')) fd.append('_csrf_token', this.csrf());
        fetch(BASE_URL + 'docente/guardarMotivoPunto', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                $('#adminModal').modal('hide');
                Swal.fire({ icon: 'success', title: d.message, timer: 900, showConfirmButton: false });
                setTimeout(() => window.location.reload(), 700);
            } else {
                Swal.fire('Error', d.message, 'error');
            }
        });
        return false;
    },
    alternar(id) {
        Swal.fire({
            title: '¿Cambiar estado del motivo?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar'
        }).then(r => {
            if (r.isConfirmed) {
                document.getElementById('alternar_motivo_id').value = id;
                document.getElementById('frmAlternarMotivo').submit();
            }
        });
    }
};
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
