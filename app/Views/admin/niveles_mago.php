<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Niveles de Magia</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-hat-wizard"></i> Niveles de gamificación</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" onclick="AdminModals.openForm('admin', 'formularioNivel', null, 'Nuevo Nivel')">
                    <i class="fas fa-plus"></i> Nuevo Nivel
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nivel</th>
                        <th>Rango</th>
                        <th>Rango de notas</th>
                        <th>Maná</th>
                        <th>Costo donación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($niveles as $nivel): ?>
                        <tr>
                            <td><?= $nivel['id'] ?></td>
                            <td>
                                <i class="<?= htmlspecialchars($nivel['icono']) ?> mr-1" style="color:#4E37E6;"></i>
                                <strong><?= htmlspecialchars($nivel['nombre']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($nivel['clave']) ?></small>
                            </td>
                            <td><?= $nivel['rango'] ?></td>
                            <td><?= number_format((float) $nivel['min_nota'], 2) ?> — <?= number_format((float) $nivel['max_nota'], 2) ?></td>
                            <td><i class="fas fa-bolt" style="color: <?= htmlspecialchars($nivel['color_mana']) ?>;"></i> <?= $nivel['mana'] ?></td>
                            <td>
                                <?php if ($nivel['costo_donacion'] !== null && (float) $nivel['costo_donacion'] > 0): ?>
                                    <span class="badge badge-success">1/<?= round(1 / (float) $nivel['costo_donacion']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No dona</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($nivel['estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleNivel', <?= $nivel['id'] ?>, 'Detalle Nivel')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioNivel', <?= $nivel['id'] ?>, 'Editar Nivel')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarNivel?id=<?= $nivel['id'] ?>', '¿Está seguro de eliminar este nivel? Debe quedar al menos un nivel activo.')">
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
