<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><i class="fas fa-chart-bar mr-1"></i> Reporte de Asistencia</h1>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-book-open mr-1"></i> <?= htmlspecialchars($curso['nombre']) ?>
                <span class="badge badge-info ml-1"><?= htmlspecialchars($curso['codigo']) ?></span>
                <small class="text-muted ml-2">Ciclo <?= htmlspecialchars($curso['ciclo']) ?></small>
            </h3>
            <div class="card-tools">
                <a href="<?= BASE_URL ?>docente/exportarAsistenciaCurso?curso_id=<?= $curso['id'] ?>"
                   class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </a>
                <a href="<?= BASE_URL ?>docente/asistencias" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if (empty($resumen)): ?>
                <div class="callout callout-info mb-0">
                    <h5>Sin datos</h5>
                    <p>No hay asistencia registrada para este curso. Tome asistencia desde
                        <a href="<?= BASE_URL ?>docente/tomarAsistencia?curso_id=<?= $curso['id'] ?>">aqu&iacute;</a>.
                    </p>
                </div>
            <?php else: ?>
                <?php
                $justMap = [];
                foreach ($justificaciones as $j) {
                    $justMap[(int) $j['estudiante_id']][] = [
                        'fecha' => $j['fecha'],
                        'obs'   => $j['observacion'] ?? '',
                    ];
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Estudiante</th>
                                <th class="text-center">Presente</th>
                                <th class="text-center">Tardanza</th>
                                <th class="text-center">Ausente</th>
                                <th class="text-center">Justificado</th>
                                <th class="text-center">Total</th>
                                <th style="min-width:200px;">% Asistencia</th>
                                <th style="min-width:220px;">Motivo de justificaci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen as $i => $r): ?>
                                <?php
                                $total = (int) $r['total'];
                                $asistio = (int) $r['presentes'] + (int) $r['tardanzas'] + (int) $r['justificados'];
                                $pct = $total > 0 ? round(($asistio / $total) * 100, 1) : 0;
                                $barColor = $pct >= 80 ? 'bg-success' : ($pct >= 60 ? 'bg-warning' : 'bg-danger');
                                $justRows = $justMap[(int) $r['estudiante_id']] ?? [];
                                ?>
                                <tr>
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['apellidos'] . ', ' . $r['nombres']) ?></strong>
                                        <small class="text-muted d-block"><?= htmlspecialchars($r['dni'] ?? '') ?></small>
                                    </td>
                                    <td class="text-center text-success font-weight-bold"><?= (int) $r['presentes'] ?></td>
                                    <td class="text-center text-warning font-weight-bold"><?= (int) $r['tardanzas'] ?></td>
                                    <td class="text-center text-danger font-weight-bold"><?= (int) $r['ausencias'] ?></td>
                                    <td class="text-center text-info font-weight-bold"><?= (int) $r['justificados'] ?></td>
                                    <td class="text-center font-weight-bold"><?= $total ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2" style="gap:10px;">
                                            <div class="progress flex-grow-1 mb-0" style="height:8px;">
                                                <div class="progress-bar <?= $barColor ?>" style="width: <?= $pct ?>%;"></div>
                                            </div>
                                            <strong style="min-width:45px;"><?= $pct ?>%</strong>
                                        </div>
                                    </td>
                                    <td class="text-muted" style="font-size:.85rem;">
                                        <?php if (empty($justRows)): ?>
                                            <span class="text-muted">—</span>
                                        <?php else: ?>
                                            <?php foreach ($justRows as $j): ?>
                                                <div>
                                                    <strong class="text-info"><?= date('d/m/Y', strtotime($j['fecha'])) ?></strong>
                                                    <?= htmlspecialchars($j['obs'] !== '' ? $j['obs'] : 'Sin motivo') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
