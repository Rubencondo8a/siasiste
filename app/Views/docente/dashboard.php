<?php
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/alerta-inasistencia.css?v=' . filemtime(ROOT_PATH . 'public/css/alerta-inasistencia.css') . '">';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../partials/messages.php';
?>

<section class="content-header">
    <h1><i class="fas fa-tachometer-alt mr-1"></i> Inicio</h1>
</section>

<section class="content">

    <?php
    $statCards = [
        [
            'icon'  => 'fas fa-book-open',
            'value' => (string) $totalCursos,
            'label' => 'Mis Cursos',
            'url'   => BASE_URL . 'docente/misCursos',
            'color' => '#7C6CF5', 'glow' => 'rgba(124,108,245,.35)',
        ],
        [
            'icon'  => 'fas fa-users',
            'value' => (string) $totalEstudiantes,
            'label' => 'Estudiantes',
            'url'   => BASE_URL . 'docente/misCursos',
            'color' => '#2563EB', 'glow' => 'rgba(37,99,235,.35)',
        ],
        [
            'icon'  => 'fas fa-clipboard-check',
            'value' => $semanasEvaluadas . ' / ' . $totalSemanas,
            'label' => 'Semanas Evaluadas',
            'url'   => BASE_URL . 'docente/semanas',
            'color' => '#0FA97A', 'glow' => 'rgba(15,169,122,.35)',
        ],
        [
            'icon'  => 'fas fa-pencil-alt',
            'value' => (string) $totalCalificaciones,
            'label' => 'Calificaciones',
            'url'   => BASE_URL . 'docente/semanas',
            'color' => '#E63777', 'glow' => 'rgba(230,55,119,.35)',
        ],
    ];

    $alertaInasistencia = $alertaInasistencia ?? [];
    $umbral = $umbralInasistencia ?? 30.0;
    $nAlerta = count($alertaInasistencia);
    $peor = $nAlerta > 0 ? $alertaInasistencia[0] : null;
    // Agrupa por unidad didactica: cada curso agrupa sus propios estudiantes en alerta
    $porCurso = [];
    foreach ($alertaInasistencia as $fila) {
        $porCurso[$fila['curso_id']]['nombre'] = $fila['curso_nombre'];
        $porCurso[$fila['curso_id']]['filas'][] = $fila;
    }
    function nivelAlerta(float $pct): string
    {
        if ($pct >= 60) return 'danger';
        if ($pct >= 45) return 'warning';
        return 'info';
    }
    ?>

    <div class="row">
        <?php foreach ($statCards as $k => $c): ?>
        <div class="col-lg-3 col-6 mb-4 d-flex">
            <a href="<?= $c['url'] ?>" class="stat-card w-100"
               style="--stat-color:<?= $c['color'] ?>; --wave-glow:<?= $c['glow'] ?>;">
                <div class="stat-card-head">
                    <div class="stat-card-icon"><i class="<?= $c['icon'] ?>"></i></div>
                    <span class="stat-card-menu"><i class="fas fa-ellipsis-v"></i></span>
                </div>
                <div class="stat-card-value"><?= $c['value'] ?></div>
                <div class="stat-card-label"><?= $c['label'] ?></div>
                <svg class="stat-card-wave" viewBox="0 0 120 30" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M2 24 C 14 24 20 8 34 11 C 48 14 54 22 68 17 C 82 12 92 4 118 7"
                          fill="none" stroke-width="2.5" stroke-linecap="round" style="stroke: var(--stat-color);"/>
                </svg>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Alerta de inasistencia -->
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline <?= $nAlerta > 0 ? 'card-warning' : 'card-success' ?>" id="alertaInasistencia">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <h3 class="card-title">
                        <i class="fas fa-user-slash mr-1"></i>
                        Alerta de inasistencia
                        <small class="text-muted ml-1" style="font-size:.75rem; font-weight:400;">
                            m&aacute;s de <?= number_format($umbral, 0) ?>% de faltas
                        </small>
                    </h3>
                    <?php if ($nAlerta > 0): ?>
                        <span class="badge badge-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= $nAlerta ?> <?= $nAlerta === 1 ? 'alerta' : 'alertas' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Todo en orden</span>
                    <?php endif; ?>
                </div>

                <?php if ($nAlerta === 0): ?>
                    <div class="card-body">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-check-circle text-success mr-1"></i>
                            Ning&uacute;n estudiante de tus cursos supera el <?= number_format($umbral, 0) ?>% de inasistencia.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="card-body py-2">
                        <div class="alert alert-warning mb-0 py-2 px-3" style="font-size:.9rem;">
                            <i class="fas fa-bell mr-1"></i>
                            <strong><?= $nAlerta ?></strong>
                            <?= $nAlerta === 1 ? 'estudiante supera' : 'estudiantes superan' ?>
                            el <?= number_format($umbral, 0) ?>% de inasistencia.
                            <?php if ($peor): ?>
                                El caso m&aacute;s alto es
                                <strong><?= htmlspecialchars($peor['apellidos'] . ', ' . $peor['nombres']) ?></strong>
                                en <?= htmlspecialchars($peor['curso_nombre']) ?>
                                con <?= number_format($peor['porcentaje'], 1) ?>%
                                (<?= (int) $peor['ausencias'] ?> de <?= (int) $peor['total'] ?> clases).
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive ai-wrap">
                        <table class="table table-sm table-hover nodt ai-table mb-0" style="font-size:.9rem;">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Unidad did&aacute;ctica</th>
                                    <th style="width:210px;">Inasistencia</th>
                                    <th class="text-center">A</th>
                                    <th class="text-center">T</th>
                                    <th class="text-center">J</th>
                                    <th class="text-center">Clases</th>
                                    <th class="text-center" style="width:90px;">Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porCurso as $cid => $grupo): ?>
                                    <tr class="ai-grupo" style="background:rgba(0,0,0,.03);">
                                        <td colspan="8">
                                            <i class="fas fa-book mr-1"></i>
                                            <strong><?= htmlspecialchars($grupo['nombre']) ?></strong>
                                            <span class="badge badge-light ml-1"><?= count($grupo['filas']) ?> en alerta</span>
                                        </td>
                                    </tr>
                                    <?php foreach ($grupo['filas'] as $f):
                                        $nivel = nivelAlerta((float) $f['porcentaje']);
                                        $pct = (float) $f['porcentaje'];
                                    ?>
                                        <tr class="ai-dato">
                                            <td data-label="Estudiante">
                                                <strong><?= htmlspecialchars($f['apellidos'] . ', ' . $f['nombres']) ?></strong>
                                                <?php if (!empty($f['dni'])): ?>
                                                    <small class="text-muted d-block">DNI <?= htmlspecialchars($f['dni']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Unidad" class="ai-unidad"><?= htmlspecialchars($f['curso_nombre']) ?></td>
                                            <td data-label="Inasistencia">
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 mr-2" style="height:8px; min-width:90px;">
                                                        <div class="progress-bar bg-<?= $nivel ?>"
                                                             role="progressbar"
                                                             style="width:<?= min(100, $pct) ?>%"
                                                             aria-valuenow="<?= round($pct) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="badge badge-<?= $nivel ?>"><?= number_format($pct, 1) ?>%</span>
                                                </div>
                                            </td>
                                            <td data-label="A" class="text-center font-weight-bold"><?= (int) $f['ausencias'] ?></td>
                                            <td data-label="T" class="text-center text-muted ai-extra"><?= (int) $f['tardanzas'] ?></td>
                                            <td data-label="J" class="text-center text-muted ai-extra"><?= (int) $f['justificados'] ?></td>
                                            <td data-label="Clases" class="text-center text-muted"><?= (int) $f['total'] ?></td>
                                            <td data-label="Detalle" class="text-center">
                                                <a href="<?= BASE_URL ?>docente/detalleInasistencia?estudiante=<?= (int) $f['estudiante_id'] ?>&amp;curso=<?= (int) $f['curso_id'] ?>"
                                                   class="btn btn-outline-primary btn-xs py-1 px-2"
                                                   title="Ver el registro clase por clase de <?= htmlspecialchars($f['nombres'] . ' ' . $f['apellidos']) ?>">
                                                    <i class="fas fa-list-ul"></i> Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer py-2" style="font-size:.8rem;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Inasistencia = clases sin asistencia (A) sobre el total de clases registradas en esa unidad.
                        Las tardanzas (T) y justificaciones (J) no aumentan el porcentaje.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($cursos)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-1"></i> Evaluaci&oacute;n por Curso</h3>
                </div>
                <div class="card-body p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Curso</th>
                                    <th class="text-center">C&oacute;digo</th>
                                    <th class="text-center">Periodo</th>
                                    <th class="text-center"><i class="fas fa-user-graduate"></i> Estudiantes</th>
                                    <th class="text-center"><i class="fas fa-calendar-week"></i> Semanas</th>
                                    <th class="text-center"><i class="fas fa-clipboard-check"></i> Evaluadas</th>
                                    <th class="text-center">Acci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursos as $i => $curso): ?>
                                <tr>
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($curso['nombre']) ?></strong></td>
                                    <td class="text-center"><span class="badge badge-info"><?= htmlspecialchars($curso['codigo']) ?></span></td>
                                    <td class="text-center"><?= htmlspecialchars($curso['periodo_nombre']) ?></td>
                                    <td class="text-center"><?= $curso['total_estudiantes'] ?></td>
                                    <td class="text-center"><?= $curso['total_semanas'] ?></td>
                                    <td class="text-center">
                                        <?php if ($curso['semanas_evaluadas'] > 0): ?>
                                            <span class="badge badge-success"><?= $curso['semanas_evaluadas'] ?> / <?= $curso['total_semanas'] ?></span>
                                        <?php elseif ($curso['total_semanas'] > 0): ?>
                                            <span class="badge badge-secondary">0 / <?= $curso['total_semanas'] ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Sin semanas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $curso['id'] ?>" class="btn btn-outline-primary" title="Semanas y Criterios">
                                                <i class="fas fa-calendar-week"></i>
                                            </a>
                                            <?php if ($curso['total_semanas'] > 0): ?>
                                            <a href="<?= BASE_URL ?>docente/notas?curso_id=<?= $curso['id'] ?>" class="btn btn-outline-success" title="Ingresar Notas">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="<?= BASE_URL ?>docente/reporteResumenCurso?curso_id=<?= $curso['id'] ?>" class="btn btn-outline-info" title="Reporte Resumen">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="callout callout-info">
                <h5>Sin cursos asignados</h5>
                <p>No tiene cursos asignados en periodos activos. Contacte al administrador.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
