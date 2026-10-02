<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<?php $cerrada = (int) ($semana['cerrada'] ?? 0); ?>

<?php if ($cerrada): ?>
<div class="alert alert-danger">
    <i class="fas fa-lock"></i> Esta semana está cerrada. No se pueden modificar los criterios.
</div>
<?php endif; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Criterios - Semana <?= $semana['numero_semana'] ?>: <?= htmlspecialchars($semana['nombre']) ?></h1>
        <?php if (!$cerrada): ?>
        <div class="d-flex flex-wrap">
            <a href="#" class="btn btn-info" onclick="AdminModals.openModal('<?= BASE_URL ?>docente/formularioCriterio?semana_id=<?= $semana['id'] ?>', 'Nuevo Criterio', 'lg'); return false;">
                <i class="fas fa-plus"></i> Nuevo Criterio
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <strong>Porcentaje asignado:</strong>
                    <div class="progress" style="height: 25px;">
                        <?php
                        $colorClass = 'bg-success';
                        if ($totalPorcentaje < 100) $colorClass = 'bg-warning';
                        if ($totalPorcentaje > 100) $colorClass = 'bg-danger';
                        ?>
                        <div class="progress-bar <?= $colorClass ?>" role="progressbar"
                             style="width: <?= min($totalPorcentaje, 100) ?>%"
                             aria-valuenow="<?= $totalPorcentaje ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= number_format($totalPorcentaje, 0) ?>%
                        </div>
                    </div>
                    <small class="text-muted">Total: <?= number_format($totalPorcentaje, 0) ?>% de 100%</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="<?= BASE_URL ?>docente/notas?semana_id=<?= $semana['id'] ?>" class="btn btn-success btn-block mt-2">
                <i class="fas fa-pen"></i> Registrar Calificaciones
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover nodt">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Porcentaje</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tipoLabels = [
                        'procedimental' => ['Saber Hacer — Procedimental', 'success'],
                        'conceptual'    => ['Saber — Conceptual', 'info'],
                        'actitudinal'   => ['Saber Ser — Actitudinal', 'warning'],
                    ];
                    ?>
                    <?php foreach ($criterios as $criterio): ?>
                        <tr>
                            <td><?= $criterio['id'] ?></td>
                            <td><?= htmlspecialchars($criterio['nombre']) ?></td>
                            <td>
                                <?php
                                $tipoActual = $criterio['tipo'] ?? 'conceptual';
                                $label = $tipoLabels[$tipoActual] ?? ['Desconocido', 'secondary'];
                                ?>
                                <span class="badge badge-<?= $label[1] ?>"><?= $label[0] ?></span>
                            </td>
                            <td><span class="badge badge-info"><?= number_format((float)$criterio['porcentaje'], 0) ?>%</span></td>
                            <td>
                                <?php if ($cerrada): ?>
                                    <span class="text-muted"><i class="fas fa-lock"></i> Cerrada</span>
                                <?php else: ?>
                                    <a href="#" class="btn btn-sm btn-warning" title="Editar" onclick="AdminModals.openModal('<?= BASE_URL ?>docente/formularioCriterio?semana_id=<?= $semana['id'] ?>&id=<?= $criterio['id'] ?>', 'Editar Criterio', 'lg'); return false;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-danger" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>docente/eliminarCriterio?semana_id=<?= $semana['id'] ?>&id=<?= $criterio['id'] ?>', '¿Está seguro de eliminar este criterio?'); return false;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $semana['curso_id'] ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver a Semanas
    </a>
</section>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = [BASE_URL . 'js/admin_modals.js'];
$inlineScripts = [];
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
