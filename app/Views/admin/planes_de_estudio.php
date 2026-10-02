<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de Planes de Estudio</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Lista de Planes de Estudio por Carrera</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-success" onclick="AdminModals.openForm('admin', 'formularioPlan', null, 'Nuevo Plan de Estudios')">
                    <i class="fas fa-plus"></i> Nuevo Plan de Estudios
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Carrera</th>
                        <th>Planes de Estudio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carreras as $carrera): ?>
                        <?php $planes = $planesPorCarrera[$carrera['id']] ?? []; ?>
                        <tr>
                            <td><?= $carrera['id'] ?></td>
                            <td><?= htmlspecialchars($carrera['nombre']) ?></td>
                            <td>
                                <?php if (empty($planes)): ?>
                                    <span class="text-muted font-italic">Sin planes</span>
                                <?php else: ?>
                                    <?php foreach ($planes as $plan): ?>
                                        <div class="d-inline-flex align-items-center mr-2 mb-1">
                                            <span class="badge <?= $plan['estado'] ? 'badge-info' : 'badge-secondary' ?> mr-1">
                                                Plan <?= htmlspecialchars($plan['nombre']) ?>
                                            </span>
                                            <button class="btn btn-outline-success btn-xs p-0 px-1 mr-1" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detallePlan', <?= $plan['id'] ?>, 'Detalle Plan de Estudios')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning btn-xs p-0 px-1 mr-1" title="Editar" onclick="AdminModals.openForm('admin', 'formularioPlan', <?= $plan['id'] ?>, 'Editar Plan de Estudios')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-xs p-0 px-1" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarPlan?id=<?= $plan['id'] ?>', '¿Está seguro de eliminar este plan de estudios?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
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
