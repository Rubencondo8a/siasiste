<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Rendimiento del Grupo - <?= htmlspecialchars($semana['nombre']) ?></h1>
        <a href="<?= BASE_URL ?>docente/reportes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    <small>Estadísticas generales de la semana <?= $semana['numero_semana'] ?></small>
</section>

<section class="content">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= $stats['total_estudiantes'] ?></h3>
                    <p>Total Estudiantes</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?= $stats['aprobados'] ?></h3>
                    <p>Aprobados (>=10)</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3><?= $stats['reprobados'] ?></h3>
                    <p>Reprobados (<10)</p>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3><?= number_format($stats['promedio_general'], 0) ?></h3>
                    <p>Promedio General</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Estadísticas</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered nodt">
                        <tr><td><strong>Estudiantes con calificaciones</strong></td><td class="text-right"><?= $stats['con_calificaciones'] ?> / <?= $stats['total_estudiantes'] ?></td></tr>
                        <tr><td><strong>Estudiantes sin calificaciones</strong></td><td class="text-right"><?= $stats['sin_calificaciones'] ?></td></tr>
                            <tr><td><strong>Nota Máxima</strong></td><td class="text-right"><span class="badge badge-success"><?= number_format($stats['nota_maxima'], 2) ?></span></td></tr>
                        <tr><td><strong>Nota Mínima</strong></td><td class="text-right"><span class="badge badge-danger"><?= number_format($stats['nota_minima'], 2) ?></span></td></tr>
                        <tr><td><strong>Promedio General</strong></td><td class="text-right"><span class="badge badge-info badge-lg"><?= number_format($stats['promedio_general'], 0) ?></span></td></tr>
                        <tr><td><strong>% Aprobados</strong></td><td class="text-right"><?= $stats['con_calificaciones'] > 0 ? number_format(($stats['aprobados'] / $stats['con_calificaciones']) * 100, 0) : 0 ?>%</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Detalle por Estudiante</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr><th>Estudiante</th><th class="text-center">Promedio</th><th class="text-center">Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $est): ?>
                                <?php $prom = $promedios[$est['id']] ?? 0; ?>
                                <tr>
                                    <td><?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?></td>
                                    <td class="text-center"><strong><?= number_format($prom, 0) ?></strong></td>
                                    <td class="text-center">
                                        <?php if ($prom >= 10): ?>
                                            <span class="badge badge-success">Aprobado</span>
                                        <?php elseif ($prom > 0): ?>
                                            <span class="badge badge-danger">Reprobado</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Sin datos</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
