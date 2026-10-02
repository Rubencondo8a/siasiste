<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Asignaciones de Cursos</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-link"></i> Lista de Asignaciones</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" onclick="AdminModals.openForm('admin', 'formularioAsignacion', null, 'Nueva Asignación')">
                    <i class="fas fa-plus"></i> Nueva Asignación
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Curso</th>
                        <th>Código</th>
                        <th>Periodo</th>
                        <th>Docente</th>
                        <th>Periodo Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asignaciones as $a): ?>
                        <tr>
                            <td><?= $a['id'] ?></td>
                            <td><?= htmlspecialchars($a['curso_nombre']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($a['codigo']) ?></span></td>
                            <td><?= htmlspecialchars($a['periodo_nombre'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($a['docente_apellidos'])): ?>
                                    <span class="badge badge-primary"><?= htmlspecialchars($a['docente_apellidos'] . ', ' . $a['docente_nombres']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['periodo_estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleAsignacion', <?= $a['id'] ?>, 'Detalle Asignación')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioAsignacion', <?= $a['id'] ?>, 'Editar Asignación')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!empty($a['docente_apellidos'])): ?>
                                    <button class="btn btn-danger btn-sm" title="Desasignar Docente" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarAsignacion?id=<?= $a['id'] ?>', '¿Desasignar docente de este curso?')">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>js/admin_modals.js"></script>
