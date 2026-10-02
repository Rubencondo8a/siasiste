<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><i class="fas fa-user-check mr-1"></i> Asistencia</h1>
</section>

<section class="content">
    <?php if (empty($cursos)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                <p class="mb-0 font-weight-bold">No tiene cursos asignados en el periodo activo.</p>
            </div>
        </div>
    <?php else: ?>
        <?php
        $iconColors = [
            '#7C6CF5', '#2563EB', '#0FA97A', '#E63777', '#9CB51F', '#2E218A',
        ];
        $i = 0;
        ?>
        <div class="row">
            <?php foreach ($cursos as $curso): ?>
                <?php $color = $iconColors[$i % count($iconColors)]; $i++; ?>
                <div class="col-md-6 col-xl-4 mb-4 d-flex">
                    <div class="course-card w-100">
                        <div class="course-card-top">
                            <div class="course-icon" style="background: <?= $color ?>;">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="course-head">
                                <span class="course-code"><?= htmlspecialchars($curso['codigo']) ?></span>
                                <h3 class="course-name" title="<?= htmlspecialchars($curso['nombre']) ?>">
                                    <?= htmlspecialchars($curso['nombre']) ?>
                                </h3>
                            </div>
                        </div>
                        <div class="course-body">
                            <div class="course-meta">
                                <i class="fas fa-layer-group"></i>
                                <span>Ciclo <?= htmlspecialchars($curso['ciclo']) ?></span>
                            </div>
                            <div class="course-stats">
                                <div class="course-stat">
                                    <i class="fas fa-users"></i>
                                    <span class="stat-num"><?= (int) $curso['total_estudiantes'] ?></span>
                                    <span class="stat-label">Estudiantes</span>
                                </div>
                                <div class="course-stat">
                                    <i class="fas fa-calendar-check"></i>
                                    <span class="stat-num"><?= (int) $curso['dias_registrados'] ?></span>
                                    <span class="stat-label">D&iacute;as</span>
                                </div>
                            </div>
                            <?php if ((int) $curso['registrados_hoy'] > 0): ?>
                                <div class="alert alert-success py-2 px-3 mb-0 mt-1" style="border-radius:8px;font-size:.78rem;">
                                    <i class="fas fa-check-circle mr-1"></i> Asistencia de hoy registrada
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="course-card-footer">
                            <div class="d-flex align-items-center flex-nowrap" style="gap:6px;">
                                <a href="<?= BASE_URL ?>docente/tomarAsistencia?curso_id=<?= $curso['id'] ?>"
                                   class="btn-course d-inline-flex align-items-center justify-content-center" style="flex:0 1 auto; padding:6px 10px; font-size:.8rem; line-height:1; border-radius:6px; min-height:36px; white-space:nowrap;" title="Tomar / corregir asistencia">
                                    <i class="fas fa-pen-alt mr-1"></i> Asistencia
                                </a>
                                <a href="<?= BASE_URL ?>docente/exportarAsistenciaCursoDetalle?curso_id=<?= $curso['id'] ?>"
                                   class="btn btn-success d-inline-flex align-items-center justify-content-center" style="line-height:1; height:36px; width:36px; padding:0; border-radius:6px; font-size:.95rem; flex:0 0 auto;" title="Exportar a Excel (.xlsx)">
                                    <i class="fas fa-file-excel"></i>
                                </a>
                                <a href="<?= BASE_URL ?>docente/exportarAsistenciaCursoTxt?curso_id=<?= $curso['id'] ?>"
                                   class="btn btn-secondary d-inline-flex align-items-center justify-content-center" style="line-height:1; height:36px; width:36px; padding:0; border-radius:6px; font-size:.95rem; flex:0 0 auto;" title="Exportar a TXT">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <a href="<?= BASE_URL ?>docente/reporteAsistencia?curso_id=<?= $curso['id'] ?>"
                                   class="btn btn-primary d-inline-flex align-items-center justify-content-center" style="line-height:1; height:36px; width:36px; padding:0; border-radius:6px; font-size:.95rem; flex:0 0 auto;" title="Reporte de asistencia">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
