<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Calificaciones - <?= htmlspecialchars($semana['nombre']) ?> (Semana <?= $semana['numero_semana'] ?>)</h1>
        <a href="<?= BASE_URL ?>docente/reportes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    <small>Periodo: <?= htmlspecialchars($semana['fecha_inicio']) ?> al <?= htmlspecialchars($semana['fecha_fin']) ?></small>
</section>

<section class="content">
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Apellidos</th>
                        <th>Nombres</th>
                        <?php foreach ($criterios as $crit): ?>
                            <th class="text-center" style="max-width:120px;" title="<?= htmlspecialchars($crit['nombre']) ?>"><span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($crit['nombre']) ?></span><small>(<?= $crit['porcentaje'] ?>%)</small></th>
                        <?php endforeach; ?>
                        <th class="text-center bg-info text-white"><strong>Promedio</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($estudiantes as $est): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($est['apellidos']) ?></td>
                                <td><?= htmlspecialchars($est['nombres']) ?></td>
                                <?php foreach ($criterios as $crit): ?>
                                    <td class="text-center">
                                        <?php
                                        $nota = $calificaciones[$est['id']][$crit['id']] ?? null;
                                        if ($nota !== null):
                                            $color = $nota >= 10 ? 'success' : 'danger';
                                        ?>
                                            <span class="badge badge-<?= $color ?>"><?= number_format((float)$nota, 0) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center bg-info">
                                    <strong><?= number_format($promedios[$est['id']] ?? 0, 0) ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
