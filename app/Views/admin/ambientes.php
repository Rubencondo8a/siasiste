<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Ambientes</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-door-open"></i> Lista de Ambientes</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-success" onclick="AdminModals.openForm('admin', 'formularioAmbiente', null, 'Nuevo Ambiente')">
                    <i class="fas fa-plus"></i> Nuevo Ambiente
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th class="text-center">Capacidad</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($ambientes as $amb): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($amb['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($amb['ubicacion']) ?: '—' ?></td>
                            <td class="text-center"><?= $amb['capacidad'] ?: '—' ?></td>
                            <td>
                                <?php if ($amb['estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-success btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleAmbiente', <?= $amb['id'] ?>, 'Detalle Ambiente')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioAmbiente', <?= $amb['id'] ?>, 'Editar Ambiente')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarAmbiente?id=<?= $amb['id'] ?>', '¿Está seguro de eliminar este ambiente?')">
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
