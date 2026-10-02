<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Resumen del Curso: <?= htmlspecialchars($curso['nombre']) ?></h1>
        <div>
            <a href="<?= BASE_URL ?>docente/exportarReporteCurso?curso_id=<?= $curso['id'] ?>&format=pdf" class="btn btn-danger mr-1">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
            <a href="<?= BASE_URL ?>docente/exportarReporteCurso?curso_id=<?= $curso['id'] ?>&format=word" class="btn btn-primary mr-1">
                <i class="fas fa-file-word mr-1"></i> Word
            </a>
            <a href="<?= BASE_URL ?>docente/exportarReporteCurso?curso_id=<?= $curso['id'] ?>&format=excel" class="btn btn-success mr-1">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <a href="<?= BASE_URL ?>docente/reportes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <small>Promedio por semana y promedio final de cada estudiante</small>
</section>

<section class="content">
    <?php if (empty($estudiantes)): ?>
        <div class="callout callout-info">
            <h5>Sin estudiantes</h5>
            <p>No hay estudiantes asignados a este curso.</p>
        </div>
    <?php elseif (empty($semanas)): ?>
        <div class="callout callout-warning">
            <h5>Sin semanas</h5>
            <p>No hay semanas creadas para este curso.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-table"></i> Matriz de Calificaciones</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover nodt">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle text-center">#</th>
                            <th rowspan="2" class="align-middle">Estudiante</th>
                            <?php foreach ($semanas as $semana):
                                $nombreLargo = htmlspecialchars($semana['nombre']);
                                $nombreCorto = mb_strlen($semana['nombre']) > 18 ? htmlspecialchars(mb_substr($semana['nombre'], 0, 15) . '…') : $nombreLargo;
                            ?>
                                <th class="text-center" title="<?= $nombreLargo ?>" style="min-width:50px;">
                                    <span style="font-size:11px;font-weight:600;"><?= $nombreCorto ?></span><br>
                                    <small>Sem <?= $semana['numero_semana'] ?></small>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center bg-info text-white">
                                <strong>Promedio<br>Final</strong>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach ($estudiantes as $est): ?>
                            <tr>
                                <td class="text-center"><?= $idx++ ?></td>
                                <td><strong><?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?></strong></td>
                                <?php foreach ($semanas as $semana):
                                    $prom = $promediosPorSemana[$est['id']][$semana['id']] ?? null;
                                    if ($prom !== null) {
                                        $color = $prom >= 10 ? 'success' : ($prom >= 6 ? 'warning' : 'danger');
                                    } else {
                                        $color = 'secondary';
                                    }
                                ?>
                                    <td class="text-center">
                                        <?php if ($prom !== null): ?>
                                            <span class="badge badge-<?= $color ?>"><?= number_format($prom, 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center bg-light">
                                    <?php
                                    $final = $promediosFinales[$est['id']] ?? null;
                                    if ($final !== null) {
                                        $fColor = $final >= 10 ? 'success' : ($final >= 6 ? 'warning' : 'danger');
                                    } else {
                                        $fColor = 'secondary';
                                    }
                                    ?>
                                    <span class="badge badge-<?= $fColor ?> badge-lg">
                                        <?= $final !== null ? number_format($final, 2) : '--' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Aprobados (>=10)</span>
                        <span class="info-box-number">
                            <?= count(array_filter($promediosFinales, fn($p) => $p !== null && $p >= 10)) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Reprobados (<10)</span>
                        <span class="info-box-number">
                            <?= count(array_filter($promediosFinales, fn($p) => $p !== null && $p < 10)) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Promedio General del Curso</span>
                        <span class="info-box-number">
                            <?php
                            $validos = array_filter($promediosFinales, fn($p) => $p !== null);
                            echo $validos ? number_format(array_sum($validos) / count($validos), 2) : '--';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
