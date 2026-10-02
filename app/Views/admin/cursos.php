<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
        <h1 class="mb-0"><i class="fas fa-book-open mr-1"></i> Gestión de Cursos</h1>
        <button type="button" class="btn btn-success" onclick="AdminModals.openForm('admin', 'formularioCurso', null, 'Nuevo Curso')">
            <i class="fas fa-plus"></i> Nuevo Curso
        </button>
    </div>
</section>

<section class="content">
    <?php
    $iconColors = ['#7C6CF5', '#2563EB', '#0FA97A', '#E63777', '#9CB51F', '#2E218A'];

    $ordinales = ['Primer', 'Segundo', 'Tercer', 'Cuarto', 'Quinto', 'Sexto', 'Séptimo', 'Octavo', 'Noveno', 'Décimo'];
    $labelCiclo = function($n) use ($ordinales) {
        $n = (int) $n;
        if ($n < 1) return '';
        return ($ordinales[$n - 1] ?? ($n . '°')) . ' Ciclo';
    };

    $maxCiclos = 0;
    foreach ($carreras ?? [] as $c) {
        $total = (int) ($c['total_ciclos'] ?? 0);
        if ($total > $maxCiclos) $maxCiclos = $total;
    }
    if ($maxCiclos < 1) $maxCiclos = 1;
    $ciclos = range(1, $maxCiclos);
    $ciclosExistentes = array_values(array_unique(array_filter(array_map('trim', array_column($cursos, 'ciclo')))));
    usort($ciclosExistentes, fn($a, $b) => (int) $a <=> (int) $b);

    $carrerasFiltro = array_values(array_unique(array_filter(array_map(fn($c) => trim($c['nombre'] ?? ''), $carreras ?? []))));
    sort($carrerasFiltro);
    $haySinCarrera = count(array_filter($cursos, fn($c) => empty($c['carrera_nombre']))) > 0;
    ?>

    <?php if (empty($cursos)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h4 class="font-weight-bold">No hay cursos registrados</h4>
                <p class="text-muted mb-3">Comienza creando el primer curso del sistema.</p>
                <button type="button" class="btn btn-success btn-lg" onclick="AdminModals.openForm('admin', 'formularioCurso', null, 'Nuevo Curso')">
                    <i class="fas fa-plus"></i> Nuevo Curso
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row align-items-end" style="gap:0;">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label for="cursoBuscar" class="mb-1" style="font-size:.8rem;"><i class="fas fa-search mr-1"></i> Buscar</label>
                        <input type="text" id="cursoBuscar" class="form-control" placeholder="Nombre o c&oacute;digo..." oninput="applyCourseFilters()">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filtroCiclo" class="mb-1" style="font-size:.8rem;"><i class="fas fa-layer-group mr-1"></i> Ciclo</label>
                        <select id="filtroCiclo" class="form-control" onchange="applyCourseFilters()">
                            <option value="">Todos</option>
                            <?php foreach ($ciclos as $cic): ?>
                                <option value="<?= $cic ?>"><?= $labelCiclo($cic) ?></option>
                            <?php endforeach; ?>
                            <?php foreach ($ciclosExistentes as $cic): ?>
                                <?php if (!in_array((string) $cic, array_map('strval', $ciclos), true)): ?>
                                    <option value="<?= htmlspecialchars($cic) ?>"><?= htmlspecialchars($cic) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filtroCarrera" class="mb-1" style="font-size:.8rem;"><i class="fas fa-graduation-cap mr-1"></i> Carrera</label>
                        <select id="filtroCarrera" class="form-control" onchange="applyCourseFilters()">
                            <option value="">Todas</option>
                            <?php foreach ($carrerasFiltro as $car): ?>
                                <option value="<?= htmlspecialchars($car) ?>"><?= htmlspecialchars($car) ?></option>
                            <?php endforeach; ?>
                            <?php if ($haySinCarrera): ?>
                                <option value="__sin_carrera__">Sin carrera</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label for="filtroEstado" class="mb-1" style="font-size:.8rem;"><i class="fas fa-toggle-on mr-1"></i> Estado</label>
                        <select id="filtroEstado" class="form-control" onchange="applyCourseFilters()">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted" style="font-size:.85rem;" id="cursoCount"><?= count($cursos) ?> curso(s)</span>
            <button type="button" class="btn btn-link btn-sm p-0" onclick="resetCourseFilters()">
                <i class="fas fa-eraser mr-1"></i> Limpiar filtros
            </button>
        </div>

        <div class="row" id="cursoGrid">
            <?php foreach ($cursos as $i => $curso): ?>
                <?php
                $color = $iconColors[$i % count($iconColors)];
                $carrera = $curso['carrera_nombre'] ?? '';
                $estado = (int) $curso['estado'];
                ?>
                <div class="col-md-6 col-xl-4 mb-4 d-flex course-col"
                     data-nombre="<?= htmlspecialchars(mb_strtolower($curso['nombre'])) ?>"
                     data-codigo="<?= htmlspecialchars(mb_strtolower($curso['codigo'])) ?>"
                     data-ciclo="<?= htmlspecialchars($curso['ciclo'] ?? '') ?>"
                     data-carrera="<?= htmlspecialchars($carrera) ?>"
                     data-estado="<?= $estado ?>">
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
                        <?php if (!empty($curso['descripcion'])): ?>
                            <div class="course-desc" title="<?= htmlspecialchars($curso['descripcion']) ?>">
                                <?= htmlspecialchars($curso['descripcion']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="course-body">
                            <div class="course-meta" title="Carrera / Programa">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?= htmlspecialchars($carrera !== '' ? $carrera : 'Sin carrera') ?></span>
                            </div>
                            <div class="course-meta-strip">
                                <div class="course-meta" title="Ciclo / Semestre">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?= $labelCiclo($curso['ciclo'] ?? '') ?: htmlspecialchars($curso['ciclo'] ?: 'N/A') ?></span>
                                </div>
                                <div class="course-meta" title="Plan de Estudios">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?= !empty($curso['plan_nombre']) ? 'Plan ' . htmlspecialchars($curso['plan_nombre']) : 'N/A' ?></span>
                                </div>
                            </div>
                            <div>
                                <?php if ($estado): ?>
                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i>Inactivo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="course-card-footer">
                            <div class="d-flex" style="gap:8px;">
                                <button type="button" class="btn btn-sm btn-outline-info flex-fill"
                                        title="Ver detalle"
                                        onclick="AdminModals.openDetail('admin', 'detalleCurso', <?= $curso['id'] ?>, 'Detalle Curso')">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                <button type="button" class="btn btn-sm btn-warning flex-fill"
                                        title="Editar"
                                        onclick="AdminModals.openForm('admin', 'formularioCurso', <?= $curso['id'] ?>, 'Editar Curso')">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button type="button" class="btn btn-sm btn-danger"
                                        title="Eliminar"
                                        onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarCurso?id=<?= $curso['id'] ?>', '&iquest;Est&aacute; seguro de eliminar este curso?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card d-none" id="cursoSinResultados">
            <div class="card-body text-center py-5">
                <i class="fas fa-filter fa-2x text-muted mb-2"></i>
                <p class="mb-0 text-muted">No hay cursos que coincidan con los filtros aplicados.</p>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>

<?php
$extraScripts = $extraScripts ?? [];
$inlineScripts = $inlineScripts ?? [];
$extraScripts[] = BASE_URL . 'js/admin_modals.js';
$inlineScripts[] = <<<'JS'
function applyCourseFilters() {
    var q = (document.getElementById('cursoBuscar').value || '').toLowerCase().trim();
    var ciclo = document.getElementById('filtroCiclo').value;
    var carrera = document.getElementById('filtroCarrera').value;
    var estado = document.getElementById('filtroEstado').value;
    var count = 0;
    document.querySelectorAll('.course-col').forEach(function (col) {
        var ok = true;
        if (q && ((col.dataset.nombre || '') + ' ' + (col.dataset.codigo || '')).indexOf(q) === -1) ok = false;
        if (ok && ciclo && col.dataset.ciclo !== ciclo) ok = false;
        if (ok && carrera) {
            if (carrera === '__sin_carrera__') {
                if (col.dataset.carrera) ok = false;
            } else if (col.dataset.carrera !== carrera) ok = false;
        }
        if (ok && estado !== '' && col.dataset.estado !== estado) ok = false;
        col.style.display = ok ? '' : 'none';
        if (ok) count++;
    });
    document.getElementById('cursoCount').textContent = count + ' curso(s)';
    document.getElementById('cursoSinResultados').classList.toggle('d-none', count > 0);
}

function resetCourseFilters() {
    document.getElementById('cursoBuscar').value = '';
    document.getElementById('filtroCiclo').value = '';
    document.getElementById('filtroCarrera').value = '';
    document.getElementById('filtroEstado').value = '';
    applyCourseFilters();
}
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
