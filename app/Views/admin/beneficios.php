<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Beneficios</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-gift"></i> Catálogo de beneficios</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" onclick="AdminModals.openForm('admin', 'formularioBeneficio', null, 'Nuevo Beneficio')">
                    <i class="fas fa-plus"></i> Nuevo Beneficio
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th class="text-center">Costo (💎)</th>
                        <th class="text-center">Canjes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista as $ben): ?>
                        <?php
                            $tipoLabel = $ben['tipo'];
                            $tipoBadge = 'secondary';
                            $tipoIcono = 'fa-question-circle';
                            if ($ben['tipo'] === 'punto_extra') { $tipoLabel = 'Punto Extra'; $tipoBadge = 'warning'; $tipoIcono = 'fa-star'; }
                            elseif ($ben['tipo'] === 'extension_plazo') { $tipoLabel = 'Extensión Plazo'; $tipoBadge = 'info'; $tipoIcono = 'fa-clock'; }
                            elseif ($ben['tipo'] === 'examen_extra') { $tipoLabel = 'Examen Extra'; $tipoBadge = 'success'; $tipoIcono = 'fa-file-alt'; }
                        ?>
                        <tr>
                            <td><?= $ben['id'] ?></td>
                            <td><strong><?= htmlspecialchars($ben['nombre']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $tipoBadge ?>">
                                    <i class="fas <?= $tipoIcono ?> mr-1"></i><?= $tipoLabel ?>
                                </span>
                            </td>
                            <td class="text-center"><i class="fas fa-gem text-info mr-1"></i><?= (int) $ben['costo_gemas'] ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= (int) $ben['total_canjes'] ?></span></td>
                            <td>
                                <?php if ($ben['estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleBeneficio', <?= $ben['id'] ?>, 'Detalle Beneficio')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioBeneficio', <?= $ben['id'] ?>, 'Editar Beneficio')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarBeneficio?id=<?= $ben['id'] ?>', '¿Está seguro de eliminar este beneficio?')">
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
