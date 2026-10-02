<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Estudiantes - <?= htmlspecialchars($curso['nombre']) ?> (<?= htmlspecialchars($curso['codigo']) ?>)</h1>
        <div>
            <a href="<?= BASE_URL ?>docente/formularioAgregarEstudiantes?curso_id=<?= $curso['id'] ?>" class="btn btn-success">
                <i class="fas fa-plus"></i> Agregar Estudiantes
            </a>
            <a href="<?= BASE_URL ?>docente/misCursos" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <small>Ciclo: <?= htmlspecialchars($curso['ciclo']) ?> | Periodo: <?= htmlspecialchars($curso['periodo_nombre']) ?></small>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-graduate"></i> Estudiantes Asignados (<?= count($asignados) ?>)</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>DNI</th>
                        <th>Nombre Completo</th>
                        <th>Carrera</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($asignados as $est): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><code><?= htmlspecialchars($est['dni'] ?? '') ?></code></td>
                                <td><?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?><br><small class="text-muted"><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($est['email'] ?? 'Sin correo') ?></small></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($est['carrera_nombre'] ?? 'N/A') ?></span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info" title="Registrar rostro"
                                            onclick="AdminModals.openModal('<?= BASE_URL ?>docente/formularioRostro?curso_id=<?= $curso['id'] ?>&estudiante_id=<?= $est['id'] ?>', 'Registrar Rostro', 'lg')">
                                        <i class="fas fa-user-circle"></i> Rostro
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" title="Remover"
                                            data-nombre="<?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?>"
                                            data-url="<?= BASE_URL ?>docente/eliminarEstudianteCurso?curso_id=<?= $curso['id'] ?>&estudiante_id=<?= $est['id'] ?>"
                                            onclick="confirmRemove(this)">
                                        <i class="fas fa-times"></i> Remover
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="modalRemove" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-1"></i> Confirmar Remoción</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de remover al estudiante <strong id="removeNombre"></strong> del curso?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a id="removeUrl" href="#" class="btn btn-danger"><i class="fas fa-times mr-1"></i> Remover</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>

<script>
function confirmRemove(btn) {
    document.getElementById('removeNombre').textContent = btn.dataset.nombre;
    document.getElementById('removeUrl').href = btn.dataset.url;
    $('#modalRemove').modal('show');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>js/admin_modals.js"></script>
