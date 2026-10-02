<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><i class="fas fa-chart-bar mr-1"></i> Reportes</h1>
</section>

<section class="content">
    <?php
    $semanasPorCurso = [];
    foreach ($semanas ?? [] as $s) {
        $semanasPorCurso[$s['curso_nombre'] ?? 'Otros'][] = $s;
    }
    ?>
    <h3 class="mb-3 text-muted text-uppercase" style="font-size:.85rem;letter-spacing:.06em;">
        <i class="fas fa-book-open mr-1"></i> Reportes por Curso
    </h3>

    <div class="row justify-content-center">
        <div class="col-md-6 col-xl-4">
            <div class="card card-info card-outline h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-th mr-1"></i> Resumen por Curso</h3>
                </div>
                <div class="card-body">
                    <p>Promedio semanal y promedio final de todos los estudiantes en un curso.</p>
                    <form action="<?= BASE_URL ?>docente/reporteResumenCurso" method="GET">
                        <div class="form-group">
                            <label>Seleccionar Curso:</label>
                            <select name="curso_id" class="form-control" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['codigo']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info btn-block"><i class="fas fa-th mr-1"></i> Generar Reporte</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card card-success card-outline h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-ol mr-1"></i> Promedio por Estudiante</h3>
                </div>
                <div class="card-body">
                    <p>Promedio general de cada estudiante en todas las semanas calificadas.</p>
                    <form action="<?= BASE_URL ?>docente/reportePromediosEstudiantes" method="GET">
                        <div class="form-group">
                            <label>Seleccionar Curso:</label>
                            <select name="curso_id" class="form-control" required>
                                <option value="0">-- Todos los cursos --</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['codigo']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-block"><i class="fas fa-list-ol mr-1"></i> Generar Reporte</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card card-primary card-outline h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-graduate mr-1"></i> Reporte por Estudiante</h3>
                </div>
                <div class="card-body">
                    <p>Detalle de notas de un estudiante específico en todas las semanas de un curso.</p>
                    <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#studentReportModal" onclick="openStudentReportModal()">
                        <i class="fas fa-user-graduate mr-1"></i> Generar Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-3 mt-4 text-muted text-uppercase" style="font-size:.85rem;letter-spacing:.06em;">
        <i class="fas fa-calendar-week mr-1"></i> Reportes por Semana
    </h3>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-primary card-outline h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-week mr-1"></i> Calificaciones por Semana</h3>
                </div>
                <div class="card-body">
                    <p>Notas de todos los estudiantes por criterio en una semana específica.</p>
                    <form action="<?= BASE_URL ?>docente/reporteNotasPorSemana" method="GET">
                        <div class="form-group">
                            <label>Seleccionar Semana:</label>
                            <select name="semana_id" class="form-control" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($semanasPorCurso as $cursoNombre => $ws): ?>
                                    <optgroup label="<?= htmlspecialchars($cursoNombre) ?>">
                                        <?php foreach ($ws as $w): ?>
                                            <option value="<?= (int) $w['id'] ?>">Semana <?= (int) $w['numero_semana'] ?> - <?= htmlspecialchars($w['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-calendar-week mr-1"></i> Generar Reporte</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-warning card-outline h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Rendimiento del Grupo</h3>
                </div>
                <div class="card-body">
                    <p>Estadísticas generales de una semana: aprobados, reprobados, promedio y notas extremas.</p>
                    <form action="<?= BASE_URL ?>docente/reporteRendimientoGrupo" method="GET">
                        <div class="form-group">
                            <label>Seleccionar Semana:</label>
                            <select name="semana_id" class="form-control" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($semanasPorCurso as $cursoNombre => $ws): ?>
                                    <optgroup label="<?= htmlspecialchars($cursoNombre) ?>">
                                        <?php foreach ($ws as $w): ?>
                                            <option value="<?= (int) $w['id'] ?>">Semana <?= (int) $w['numero_semana'] ?> - <?= htmlspecialchars($w['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-chart-line mr-1"></i> Generar Reporte</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="studentReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-graduate mr-1"></i> Reporte por Estudiante</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="font-weight-bold">1. Seleccionar Curso</label>
                        <select id="modalCursoSelect" class="form-control" onchange="loadStudents(this.value)">
                            <option value="">-- Seleccionar curso --</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['codigo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-block" onclick="resetStudentModal()">
                            <i class="fas fa-redo mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>

                <div id="studentListContainer">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-arrow-up" style="font-size:1.5rem;"></i>
                        <p class="mt-2 mb-0">Seleccione un curso para ver los estudiantes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openStudentReportModal() {
    document.getElementById('modalCursoSelect').value = '';
    document.getElementById('studentListContainer').innerHTML =
        '<div class="text-center text-muted py-4">' +
        '<i class="fas fa-arrow-up" style="font-size:1.5rem;"></i>' +
        '<p class="mt-2 mb-0">Seleccione un curso para ver los estudiantes</p></div>';
}
function resetStudentModal() {
    document.getElementById('modalCursoSelect').value = '';
    openStudentReportModal();
}
function loadStudents(cursoId) {
    var container = document.getElementById('studentListContainer');
    if (!cursoId) {
        openStudentReportModal();
        return;
    }
    container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-lg"></i><p class="mt-2 mb-0 text-muted">Cargando estudiantes...</p></div>';
    fetch('<?= BASE_URL ?>docente/obtenerEstudiantesCurso?curso_id=' + cursoId)
        .then(function(r) { return r.text(); })
        .then(function(html) { container.innerHTML = html; });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
