<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de Carreras</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-graduation-cap"></i> Lista de Carreras</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-success" onclick="AdminModals.openForm('admin', 'formularioCarrera', null, 'Nueva Carrera')">
                    <i class="fas fa-plus"></i> Nueva Carrera
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Planes de Estudios</th>
                        <th>Total Ciclos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carreras as $carrera): ?>
                        <tr>
                            <td><?= $carrera['id'] ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($carrera['codigo']) ?></span></td>
                            <td><?= htmlspecialchars($carrera['nombre']) ?></td>
                            <td>
                                <?php $planes = $planesPorCarrera[$carrera['id']] ?? []; ?>
                                <?php if (empty($planes)): ?>
                                    <span class="text-muted font-italic">Sin planes</span>
                                <?php else: ?>
                                    <?php foreach ($planes as $plan): ?>
                                        <span class="badge badge-secondary mr-1">Plan <?= htmlspecialchars($plan['nombre']) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $carrera['total_ciclos'] ?> ciclos</td>
                            <td>
                                <?php if ($carrera['estado']): ?>
                                    <span class="badge badge-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group" aria-label="Acciones">
                                    <button class="btn btn-success btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleCarrera', <?= $carrera['id'] ?>, 'Detalle Carrera')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioCarrera', <?= $carrera['id'] ?>, 'Editar Carrera')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarCarrera?id=<?= $carrera['id'] ?>', '¿Está seguro de eliminar esta carrera?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
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
