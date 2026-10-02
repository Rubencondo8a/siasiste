<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Periodos Académicos</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Lista de Periodos</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" onclick="AdminModals.openForm('admin', 'formularioPeriodo', null, 'Nuevo Periodo')">
                    <i class="fas fa-plus"></i> Nuevo Periodo
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periodos as $periodo): ?>
                        <tr>
                            <td><?= $periodo['id'] ?></td>
                            <td><?= htmlspecialchars($periodo['nombre']) ?></td>
                            <td><?= formatDate($periodo['fecha_inicio']) ?></td>
                            <td><?= formatDate($periodo['fecha_fin']) ?></td>
                            <td>
                                <?php if ($periodo['estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detallePeriodo', <?= $periodo['id'] ?>, 'Detalle Periodo')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioPeriodo', <?= $periodo['id'] ?>, 'Editar Periodo')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarPeriodo?id=<?= $periodo['id'] ?>', '¿Está seguro? Los cursos asociados perderán el periodo.')">
                                    <i class="fas fa-trash"></i>
                                </button>
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
