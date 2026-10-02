<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>Detalle - Semana <?= $semana['numero_semana'] ?>: <?= htmlspecialchars($semana['nombre']) ?></h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Mis Calificaciones</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Criterio</th>
                                <th class="text-center">Porcentaje</th>
                                <th class="text-center">Mi Nota</th>
                                <th class="text-center">Calificación Parcial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?= htmlspecialchars($detalle['nombre']) ?></td>
                                    <td class="text-center"><?= number_format((float)$detalle['porcentaje'], 0) ?>%</td>
                                    <td class="text-center">
                                        <?php if ($detalle['nota'] !== null): ?>
                                            <?= number_format((float)$detalle['nota'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($detalle['nota'] !== null): ?>
                                            <?php $parcial = (float)$detalle['nota'] * (float)$detalle['porcentaje'] / 100; ?>
                                            <span class="badge badge-secondary"><?= number_format($parcial, 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="3"><strong>Promedio Semanal</strong></td>
                                <td class="text-center">
                                    <?php
                                    $promClass = 'badge-success';
                                    if ($promedioSemana < 10) $promClass = 'badge-warning';
                                    if ($promedioSemana < 6) $promClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?= $promClass ?> badge-lg">
                                        <?= number_format($promedioSemana, 2) ?>
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">Resumen</h3>
                </div>
                <div class="card-body">
                    <p><strong>Semana:</strong> <?= $semana['numero_semana'] ?></p>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($semana['nombre']) ?></p>
                    <p><strong>Inicio:</strong> <?= formatDate($semana['fecha_inicio']) ?></p>
                    <p><strong>Fin:</strong> <?= formatDate($semana['fecha_fin']) ?></p>
                    <hr>
                    <p><strong>Mi Promedio:</strong>
                        <?php
                        $promClass = 'badge-success';
                        if ($promedioSemana < 10) $promClass = 'badge-warning';
                        if ($promedioSemana < 6) $promClass = 'badge-danger';
                        ?>
                        <span class="badge <?= $promClass ?> badge-lg">
                            <?= number_format($promedioSemana, 2) ?>
                        </span>
                    </p>
                </div>
            </div>

            <a href="<?= BASE_URL ?>estudiante/inicio" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
