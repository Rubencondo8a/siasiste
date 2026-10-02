<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<?php
// Escala de colores por nota (0-20): rojo <9, naranja 9-<12, amarillo 12-<15, verde 15-<18, púrpura >=18
function colorNota($v): array {
    if ($v === null) return ['#6c757d', '#fff'];
    if ($v < 9)       return ['#dc3545', '#fff'];      // rojo
    if ($v < 12)      return ['#fd7e14', '#fff'];      // naranja
    if ($v < 15)      return ['#ffc107', '#212529'];   // amarillo
    if ($v < 18)      return ['#28a745', '#fff'];      // verde
    return              ['#6f42c1', '#fff'];             // púrpura
}
?>

<section class="content-header">
    <h1>
        <i class="fas fa-user-graduate mr-1"></i> Reporte por Estudiante
        <?php if ($estudiante): ?>
            <small class="text-muted ml-2"><?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></small>
        <?php endif; ?>
    </h1>
</section>

<section class="content">

    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#studentReportModal" onclick="openStudentReportModal()">
            <i class="fas fa-sync-alt mr-1"></i> <?= $estudiante ? 'Cambiar Estudiante' : 'Seleccionar Curso y Estudiante' ?>
        </button>
    </div>

    <?php if ($estudiante): ?>
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-id-card mr-1"></i>
                <?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?>
                <small class="text-muted ml-2">DNI: <?= htmlspecialchars($estudiante['dni']) ?></small>
            </h3>
            <div class="card-tools">
                <?php if ($promedioFinal >= 0): ?>
                    <?php $c = colorNota($promedioFinal); ?>
                    <span class="badge" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;font-size:.9rem;"><i class="fas fa-graduation-cap mr-1"></i>Final: <?= number_format($promedioFinal, 2) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0 overflow-hidden">
            <?php if (empty($semanas)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-folder-open" style="font-size:2rem;"></i><br>
                    No hay semanas registradas para este curso.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered nodt mb-0">
                        <thead>
                            <tr>
                                <th style="width:140px;">Semana</th>
                                <th>Criterio</th>
                                <th style="width:80px;" class="text-center">% Peso</th>
                                <th style="width:80px;" class="text-center">Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semanas as $sem): ?>
                                <?php
                                $rowspan = count($sem['notas']) > 0 ? count($sem['notas']) : 1;
                                $hasGrade = $sem['promedio'] !== null;
                                ?>
                                <?php foreach ($sem['notas'] as $idx => $nota): ?>
                                <tr>
                                    <?php if ($idx === 0): ?>
                                        <td rowspan="<?= $rowspan ?>" class="align-middle font-weight-bold text-primary" style="font-size:.85rem;">
                                            <?= htmlspecialchars($sem['nombre']) ?>
                                            <br><small class="text-muted">Sem <?= $sem['numero_semana'] ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td style="font-size:.85rem;"><?= htmlspecialchars($nota['criterio_nombre']) ?></td>
                                    <td class="text-center" style="font-size:.85rem;"><?= $nota['porcentaje'] ?>%</td>
                                    <td class="text-center" style="font-size:.85rem;">
                                        <?php if ($nota['nota'] !== null): ?>
                                            <?php $cn = colorNota($nota['nota']); ?>
                                            <span class="badge" style="background:<?= $cn[0] ?>;color:<?= $cn[1] ?>;font-size:.85rem;"><?= number_format($nota['nota'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if ($hasGrade): ?>
                                <tr class="table-active">
                                    <td colspan="3" class="text-right font-weight-bold" style="font-size:.85rem;">Promedio semana:</td>
                                    <td class="text-center">
                                        <strong style="font-size:.9rem;">
                                            <?php $cs = colorNota($sem['promedio']); ?>
                                            <span class="badge" style="background:<?= $cs[0] ?>;color:<?= $cs[1] ?>;font-size:.85rem;"><?= number_format($sem['promedio'], 2) ?></span>
                                        </strong>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td colspan="3" class="text-right font-weight-bold">PROMEDIO FINAL:</td>
                                <td class="text-center">
                                    <strong style="font-size:1.1rem;">
                                        <?php $cf = colorNota($promedioFinal); ?>
                                        <span class="badge" style="background:<?= $cf[0] ?>;color:<?= $cf[1] ?>;font-size:1.1rem;"><?= number_format($promedioFinal, 2) ?></span>
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-right mb-3">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print mr-1"></i> Imprimir
        </button>
    </div>

    <?php else: ?>
        <div class="callout callout-info">
            <h5>Seleccione un curso y estudiante</h5>
            <p>Haga clic en el botón de arriba para elegir el curso y estudiante a consultar.</p>
        </div>
    <?php endif; ?>

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
    var sel = document.getElementById('modalCursoSelect');
    if (<?= $cursoId > 0 ? 'true' : 'false' ?>) {
        sel.value = '<?= $cursoId ?>';
        loadStudents(sel.value);
    } else {
        sel.value = '';
        resetStudentModal();
    }
}
function resetStudentModal() {
    document.getElementById('modalCursoSelect').value = '';
    document.getElementById('studentListContainer').innerHTML =
        '<div class="text-center text-muted py-4">' +
        '<i class="fas fa-arrow-up" style="font-size:1.5rem;"></i>' +
        '<p class="mt-2 mb-0">Seleccione un curso para ver los estudiantes</p></div>';
}
function loadStudents(cursoId) {
    var container = document.getElementById('studentListContainer');
    if (!cursoId) {
        resetStudentModal();
        return;
    }
    container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-lg"></i><p class="mt-2 mb-0 text-muted">Cargando estudiantes...</p></div>';
    fetch('<?= BASE_URL ?>docente/obtenerEstudiantesCurso?curso_id=' + cursoId)
        .then(function(r) { return r.text(); })
        .then(function(html) { container.innerHTML = html; });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
