<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<style>
.pa-btn,
.pa-btn:hover,
.pa-btn:focus,
.pa-btn:active,
.pa-btn:not(:disabled):not(.disabled):active,
.pa-btn:not(:disabled):not(.disabled):active:focus,
.pa-btn.disabled,
.pa-btn:disabled {
    box-shadow: none !important;
    outline: none !important;
}
.pa-btn.btn-success,
.pa-btn.btn-success:hover,
.pa-btn.btn-success:focus,
.pa-btn.btn-success:active {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: #fff !important;
}
.pa-btn.btn-danger,
.pa-btn.btn-danger:hover,
.pa-btn.btn-danger:focus,
.pa-btn.btn-danger:active,
.pa-btn.btn-danger.disabled,
.pa-btn.btn-danger:disabled {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #fff !important;
}
</style>

<section class="content-header">
    <h1><i class="fas fa-book mr-1"></i> Mis Cursos</h1>
</section>

<section class="content">
    <?php if (empty($cursos)): ?>
        <div class="callout callout-info">
            <h5>Sin cursos asignados</h5>
            <p>No tiene cursos asignados actualmente.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($cursos as $c): ?>
                <?php
                $colores = ['primary','success','info','warning','danger'];
                $color = $colores[array_search($c, $cursos) % count($colores)];
                $avance = $c['total_semanas'] > 0 ? round(($c['semanas_calificadas'] / $c['total_semanas']) * 100) : 0;
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-outline card-<?= $color ?> h-100">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold"><?= htmlspecialchars($c['nombre']) ?></h3>
                            <span class="badge badge-<?= $color ?> float-right"><?= htmlspecialchars($c['codigo']) ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0" style="font-size:.88rem;">
                                <li class="mb-1">
                                    <i class="fas fa-graduation-cap text-muted mr-1"></i>
                                    Ciclo: <strong><?= htmlspecialchars($c['ciclo']) ?></strong>
                                </li>
                                <li class="mb-1">
                                    <i class="fas fa-calendar text-muted mr-1"></i>
                                    Periodo: <strong><?= htmlspecialchars($c['periodo_nombre']) ?></strong>
                                </li>
                                <?php if ($c['docente_nombres']): ?>
                                <li class="mb-1">
                                    <i class="fas fa-chalkboard-teacher text-muted mr-1"></i>
                                    Docente: <strong><?= htmlspecialchars($c['docente_apellidos'] . ', ' . $c['docente_nombres']) ?></strong>
                                </li>
                                <?php endif; ?>
                                <li class="mb-1">
                                    <i class="fas fa-calendar-check text-muted mr-1"></i>
                                    Semanas: <strong><?= $c['semanas_calificadas'] ?>/<?= $c['total_semanas'] ?></strong>
                                </li>
                                <?php if ($c['grupo_actual'] && $c['semana_actual_id']): ?>
                                <li class="mb-1">
                                    <i class="fas fa-users text-muted mr-1"></i>
                                    <a href="#" class="badge badge-primary" style="cursor:pointer;" onclick="verMiGrupo(<?= $c['semana_actual_id'] ?>); return false;">
                                        <?php 
                                        // Mostrar nombre del grupo de forma segura
                                        $gn = '';
                                        if (is_array($c['grupo_actual'])) {
                                            $gn = $c['grupo_actual']['grupo_nombre'] 
                                                ?? ($c['grupo_actual']['nombre'] ?? 'Sin grupo');
                                        }
                                        echo htmlspecialchars($gn);
                                        ?>
                                    </a>
                                    <small class="text-muted">(Semana <?= $c['numero_semana_actual'] ?>)</small>
                                </li>
                                <?php endif; ?>
                            </ul>
                            <div class="progress mt-2" style="height:8px;">
                                <div class="progress-bar bg-<?= $color ?>" style="width:<?= $avance ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $avance ?>% calificado</small>
                            <div class="mt-2 pt-2" style="border-top:1px solid #e9ecef;">
                                <?php $ptsColor = $c['total_puntos'] < 0 ? 'danger' : ($c['total_puntos'] > 0 ? 'success' : 'secondary'); ?>
                                <span class="badge badge-<?= $ptsColor ?> mr-1" title="Participaci&oacute;n acumulada"><i class="fas fa-star"></i> <?= $c['total_puntos'] ?> pts</span>
                                <span class="badge badge-info mr-1" title="Cristales"><i class="fas fa-gem"></i> <?= $c['cristales'] ?> 💎</span>
                                <?php if ($c['total_apreciaciones'] != 0): ?>
                                    <?php $apColor = $c['total_apreciaciones'] > 0 ? 'success' : 'danger'; ?>
                                    <span class="badge badge-<?= $apColor ?> mr-1" title="Apreciaci&oacute;n docente"><i class="fas fa-sliders-h"></i> <?= $c['total_apreciaciones'] > 0 ? '+' : '' ?><?= number_format($c['total_apreciaciones'], 1) ?></span>
                                <?php endif; ?>
                                <?php if ($c['sobrante_puntos'] > 0): ?>
                                    <small class="text-muted">(faltan <?= 16 - $c['sobrante_puntos'] ?> para 1💎)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="course-actions">
                                <button type="button" class="btn btn-sm btn-outline-<?= $color ?>" onclick="showCourseNotes(<?= $c['id'] ?>)">
                                    <i class="fas fa-eye"></i> Notas
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-<?= $color ?>" onclick="showCourseAttendance(<?= $c['id'] ?>)">
                                    <i class="fas fa-user-check"></i> Asistencia
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-<?= $color ?>" onclick="showCourseChart(<?= $c['id'] ?>)">
                                    <i class="fas fa-chart-bar"></i> Est.
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-<?= $color ?>" onclick="showCoursePoints(<?= $c['id'] ?>)">
                                    <i class="fas fa-star"></i> Participaci&oacute;n
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-<?= $color ?>" onclick="showCourseApreciaciones(<?= $c['id'] ?>)">
                                    <i class="fas fa-sliders-h"></i> Apreciaci&oacute;n
                                </button>
                                <a href="<?= BASE_URL ?>estudiante/discusionesPorCurso?curso_id=<?= $c['id'] ?>"
                                   class="btn btn-sm btn-outline-<?= $color ?>">
                                    <i class="fas fa-comments"></i> Debates
                                </a>
                                <?php if (!empty($c['sillabus_pdf'])): ?>
                                    <a href="<?= BASE_URL ?>docente/verSillabus?curso_id=<?= $c['id'] ?>"
                                       class="btn btn-sm btn-outline-<?= $color ?>" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Ver Sílabo
                                    </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>estudiante/tienda"
                                   class="btn btn-sm btn-outline-warning" title="Canjear cristales en la tienda">
                                    <i class="fas fa-store"></i> Tienda
                                </a>
                                <div id="paContainer-<?= $c['id'] ?>" class="mt-2 pt-2 d-none"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="courseNotesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="fas fa-file-alt mr-1"></i> Notas del Curso</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="courseNotesBody" style="max-height:70vh; overflow-y:auto;">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="courseChartModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white"><i class="fas fa-chart-bar mr-1"></i> Estadísticas del Curso</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="max-height:75vh; overflow-y:auto;">
                <div id="chartModalBody">
                    <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="courseAttendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white"><i class="fas fa-user-check mr-1"></i> Mi Asistencia</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="courseAttendanceBody" style="max-height:70vh; overflow-y:auto;">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="coursePointsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-star mr-1"></i> Mi Participaci&oacute;n</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="coursePointsBody" style="max-height:70vh; overflow-y:auto;">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="courseApreciacionesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="fas fa-sliders-h mr-1"></i> Apreciaci&oacute;n Docente</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="courseApreciacionesBody" style="max-height:70vh; overflow-y:auto;">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="miGrupoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title text-white"><i class="fas fa-users mr-1"></i> Mis Grupos</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="miGrupoBody" style="max-height:70vh; overflow-y:auto;">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
var currentCursoId = 0;
var chartInstance = null;
var attChartInstance = null;

function buildAttendanceHtml(a) {
    if (!a || !a.total) {
        return '<hr><h6 class="text-info mb-2"><i class="fas fa-chart-line mr-1"></i> Evolución de Asistencia</h6>' +
               '<div class="text-center text-muted py-2"><i class="fas fa-user-clock mr-1"></i>Sin asistencia registrada</div>';
    }
    var h = '<hr><h6 class="text-info mb-2"><i class="fas fa-chart-line mr-1"></i> Evolución de Asistencia</h6>';
    h += '<div style="position:relative;height:200px;"><canvas id="attCanvas"></canvas></div>';
    return h;
}

function renderAttChart(fechas, pres, tar, aus, just) {
    if (attChartInstance) attChartInstance.destroy();
    if (!fechas || fechas.length === 0) return;
    var ctx = document.getElementById('attCanvas');
    if (!ctx) return;
    var series = [
        { label: 'Presente',    data: pres, color: '#28a745' },
        { label: 'Tardanza',    data: tar,  color: '#ffc107' },
        { label: 'Ausente',     data: aus,  color: '#dc3545' },
        { label: 'Justificado', data: just, color: '#17a2b8' }
    ];
    var datasets = series.map(function(s) {
        return {
            label: s.label,
            data: s.data,
            borderColor: s.color,
            backgroundColor: s.color + '1a',
            borderWidth: 2,
            pointBackgroundColor: s.color,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: false,
            tension: 0,
            spanGaps: true
        };
    });
    attChartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels: fechas, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { stepSize: 10, callback: function(v) { return v + '%'; } } },
                x: { ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 12 } }
            },
            plugins: {
                legend: { display: true, position: 'top', labels: { boxWidth: 12, boxHeight: 12, usePointStyle: true } },
                tooltip: {
                    callbacks: {
                        label: function(c) { return c.dataset.label + ': ' + c.parsed.y.toFixed(1) + '%'; }
                    }
                }
            }
        },
        plugins: [{
            id: 'attLimit',
            afterDraw: function(chart) {
                var y = chart.scales.y.getPixelForValue(30);
                if (y === null || y === undefined) return;
                var c = chart.ctx;
                c.save();
                c.strokeStyle = 'rgba(220,53,69,.7)';
                c.setLineDash([6, 4]);
                c.lineWidth = 2;
                c.beginPath();
                c.moveTo(chart.chartArea.left, y);
                c.lineTo(chart.chartArea.right, y);
                c.stroke();
                c.setLineDash([]);
                c.fillStyle = '#dc3545';
                c.font = 'bold 11px Arial';
                c.textAlign = 'left';
                c.fillText('Límite 30% inasistencia', chart.chartArea.left + 6, y - 5);
                c.restore();
            }
        }]
    });
}

function showCourseNotes(cursoId) {
    currentCursoId = cursoId;
    var body = document.getElementById('courseNotesBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    $('#courseNotesModal').modal('show');
    fetch('<?= BASE_URL ?>estudiante/detalleCursoAjax?curso_id=' + cursoId)
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; });
}

function showCourseChart(cursoId) {
    currentCursoId = cursoId;
    $('#courseChartModal').modal('show');
    var body = document.getElementById('chartModalBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';

    fetch('<?= BASE_URL ?>estudiante/graficoCursoAjax?curso_id=' + cursoId)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.error || !res.success) {
                body.innerHTML = '<div class="text-center text-muted py-3">' + (res.error || 'Error') + '</div>';
                return;
            }
            body.innerHTML = '<div style="position:relative;height:300px;"><canvas id="chartCanvas"></canvas></div>' + buildAttendanceHtml(res.asistencia);
            var ctx = document.getElementById('chartCanvas').getContext('2d');
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: res.labels,
                    datasets: [{
                        label: 'Promedio',
                        data: res.data,
                        backgroundColor: res.colors.map(function(c) { return c + '99'; }),
                        borderColor: res.colors,
                        borderWidth: 2,
                        borderRadius: 4,
                        maxBarThickness: 50,
                        order: 2
                    }, {
                        label: 'Evolución',
                        type: 'line',
                        data: res.data,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,.08)',
                        borderWidth: 2,
                        pointBackgroundColor: res.colors,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0,
                        order: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 20, ticks: { stepSize: 2 } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) { return ctx.parsed.y.toFixed(2); }
                            }
                        }
                    },
                },
                plugins: [{
                    id: 'customLabels',
                    afterDraw: function(chart) {
                        var ctx2 = chart.ctx;
                        chart.data.datasets[0].data.forEach(function(value, i) {
                            if (value === null) return;
                            var meta = chart.getDatasetMeta(0);
                            var bar = meta.data[i];
                            if (!bar || !bar.x || !bar.y) return;
                            ctx2.fillStyle = '#333';
                            ctx2.font = 'bold 11px Arial';
                            ctx2.textAlign = 'center';
                            ctx2.textBaseline = 'bottom';
                            ctx2.fillText(value.toFixed(2), bar.x, bar.y - 3);
                        });
                    }
                }]
            });
            renderAttChart(res.attFechas, res.attPres, res.attTar, res.attAus, res.attJust);
        })
        .catch(function() {
            body.innerHTML = '<div class="text-center text-muted py-3">Error al cargar estadísticas</div>';
        });
}

function showWeekDetail(semanaId) {
    var body = document.getElementById('courseNotesBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    fetch('<?= BASE_URL ?>estudiante/verSemanaAjax?semana_id=' + semanaId)
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; });
}

function showCourseDetailAjax() {
    if (currentCursoId > 0) showCourseNotes(currentCursoId);
}

function showCourseAttendance(cursoId) {
    var body = document.getElementById('courseAttendanceBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    $('#courseAttendanceModal').modal('show');
    fetch('<?= BASE_URL ?>estudiante/asistenciaCursoAjax?curso_id=' + cursoId)
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; });
}

function showCoursePoints(cursoId) {
    var body = document.getElementById('coursePointsBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    $('#coursePointsModal').modal('show');
    fetch('<?= BASE_URL ?>estudiante/detallePuntosCurso?curso_id=' + cursoId)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) { body.innerHTML = '<div class="text-center text-muted py-3">' + (res.message || 'Error') + '</div>'; return; }
            var d = res.data;
            var html = '<div class="row mb-3"><div class="col-sm-4 text-center"><div class="text-success" style="font-size:2rem;font-weight:700;"><i class="fas fa-star"></i> ' + d.total_puntos + '</div><small class="text-muted">Puntos totales</small></div>';
            html += '<div class="col-sm-4 text-center"><div class="text-info" style="font-size:2rem;font-weight:700;"><i class="fas fa-gem"></i> ' + d.cristales + '</div><small class="text-muted">Cristales</small></div>';
            html += '<div class="col-sm-4 text-center"><div class="text-secondary" style="font-size:2rem;font-weight:700;">' + d.sobrante + '</div><small class="text-muted">Faltan ' + (d.sobrante > 0 ? (16 - d.sobrante) : 16) + ' para 1💎</small></div></div>';
            html += '<div class="progress mb-3" style="height:8px;"><div class="progress-bar bg-info" style="width:' + Math.round(d.sobrante / 16 * 100) + '%"></div></div>';
            if (d.detalle.length === 0) {
                html += '<div class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i>No hay registros de puntos aún.</div>';
            } else {
                html += '<table class="table table-bordered table-striped table-sm mb-0"><thead><tr><th class="text-center">Semana</th><th>Nombre</th><th class="text-center">Puntos</th><th>Motivo</th></tr></thead><tbody>';
                d.detalle.forEach(function(r) {
                    var ptsClass = r.puntos < 0 ? 'danger' : (r.puntos > 0 ? 'success' : 'secondary');
                    html += '<tr><td class="text-center">' + r.numero_semana + '</td><td>' + (r.semana_nombre || '—') + '</td><td class="text-center"><span class="badge badge-' + ptsClass + '">' + (r.puntos >= 0 ? '+' : '') + r.puntos + '</span></td><td class="text-muted small">' + (r.motivo || '—') + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            body.innerHTML = html;
        })
        .catch(function() { body.innerHTML = '<div class="text-center text-muted py-3">Error al cargar datos.</div>'; });
}

function showCourseApreciaciones(cursoId) {
    var body = document.getElementById('courseApreciacionesBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    $('#courseApreciacionesModal').modal('show');
    fetch('<?= BASE_URL ?>estudiante/detalleApreciacionesCurso?curso_id=' + cursoId)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) { body.innerHTML = '<div class="text-center text-muted py-3">' + (res.message || 'Error') + '</div>'; return; }
            var d = res.data;
            var color = d.total > 0 ? 'success' : (d.total < 0 ? 'danger' : 'secondary');
            var signo = d.total > 0 ? '+' : '';
            var html = '<div class="text-center mb-3"><div class="text-' + color + '" style="font-size:2rem;font-weight:700;"><i class="fas fa-sliders-h"></i> ' + signo + d.total.toFixed(1) + '</div><small class="text-muted">Total de ajustes docente</small></div>';
            if (d.apreciaciones.length === 0) {
                html += '<div class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i>No hay ajustes registrados.</div>';
            } else {
                html += '<table class="table table-bordered table-striped table-sm mb-0"><thead><tr><th class="text-center">Semana</th><th>Nombre</th><th class="text-center">Ajuste</th><th>Motivo</th></tr></thead><tbody>';
                d.apreciaciones.forEach(function(r) {
                    var ptsClass = r.puntos < 0 ? 'danger' : 'success';
                    html += '<tr><td class="text-center">' + r.numero_semana + '</td><td>' + (r.semana_nombre || '—') + '</td><td class="text-center"><span class="badge badge-' + ptsClass + '">' + (r.puntos > 0 ? '+' : '') + r.puntos.toFixed(1) + '</span></td><td class="text-muted small">' + (r.motivo || '—') + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            body.innerHTML = html;
        })
        .catch(function() { body.innerHTML = '<div class="text-center text-muted py-3">Error al cargar datos.</div>'; });
}

function autoRegistrarParticipacion(participacionId) {
    fetch('<?= BASE_URL ?>estudiante/autoRegistrarParticipacion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'participacion_id=' + participacionId +
              '&_csrf_token=' + encodeURIComponent($('meta[name=csrf-token]').attr('content'))
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            var btn = document.getElementById('btnParticipar-' + participacionId);
            if (btn) {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-danger');
                btn.classList.add('disabled');
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Te registraste en el orden #' + res.orden;
            }
            setTimeout(refrescarParticipacionesEstudiante, 500);
            Swal.fire({ icon: 'success', title: '¡Registrado!', text: res.message, timer: 2500, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo registrar.' });
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
}

function renderPaContainer(cursoId, pa) {
    var cont = document.getElementById('paContainer-' + cursoId);
    if (!cont) return;
    var html = '';
    if (pa) {
        var registrado = !!pa.ya_registrado;
        var btnId = 'btnParticipar-' + pa.id;
        var btnClass = registrado ? 'btn-danger' : 'btn-success';
        var btnLabel = registrado ? 'Ya te registraste' : '¡Quiero participar!';
        html += '<small class="text-muted d-block mb-1"><i class="fas fa-comment-dots text-warning"></i> El docente habilit&oacute; participaci&oacute;n (Semana ' + pa.numero_semana + ')</small>';
        html += '<button type="button" class="btn btn-sm btn-block ' + btnClass + ' ' + (registrado ? 'disabled' : '') + ' pa-btn ' + '" id="' + btnId + '" data-pid="' + pa.id + '" onclick="autoRegistrarParticipacion(' + pa.id + '); return false;" style="box-shadow:none;">' +
                '<i class="fas fa-hand-paper mr-1"></i> ' + btnLabel + '</button>';
        cont.classList.remove('d-none');
    } else {
        cont.classList.add('d-none');
    }
    cont.innerHTML = html;
}

function refrescarParticipacionesEstudiante() {
    fetch('<?= BASE_URL ?>estudiante/miParticipacionActiva')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) return;
            // curso_id -> participacion activa
            var activas = {};
            (res.participaciones || []).forEach(function(pa) {
                activas[pa.curso_id] = pa;
            });
            document.querySelectorAll('[id^="paContainer-"]').forEach(function(cont) {
                var cursoId = parseInt(cont.id.replace('paContainer-', ''), 10);
                renderPaContainer(cursoId, activas[cursoId] || null);
            });
        })
        .catch(function() {});
}

function verMiGrupo(semanaId) {    var body = document.getElementById('miGrupoBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    $('#miGrupoModal').modal('show');
    fetch('<?= BASE_URL ?>estudiante/gruposSemanaAjax?semana_id=' + semanaId)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) { body.innerHTML = '<div class="text-center text-muted py-3">' + (res.message || 'Error') + '</div>'; return; }
            var s = res.semana;
            var grupos = res.grupos;
            var miId = res.mi_estudiante_id;  // Ya no usamos mi_grupo_id, sino el ID del estudiante
            var html = '<h6 class="mb-3"><i class="fas fa-calendar mr-1"></i> Semana ' + s.numero_semana + ': ' + (s.nombre || '') + '</h6>';
            if (grupos.length === 0) {
                html += '<div class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i>No hay grupos en esta semana.</div>';
            } else {
                grupos.forEach(function(g) {
                    var esMiGrupo = (miId && g.id == miId);
                    var headerClass = esMiGrupo ? 'bg-primary text-white' : 'bg-light';
                    // Ícono/badge "Mi Grupo" solo en el header
                    var headerIcon = esMiGrupo ? ' <span class="badge badge-warning text-dark ml-1"><i class="fas fa-star mr-1"></i>Mi Grupo</span>' : '';
                    html += '<div class="card mb-3 ' + (esMiGrupo ? 'border-primary' : '') + '">';
                    html += '<div class="card-header ' + headerClass + ' py-2"><strong><i class="fas fa-users mr-1"></i> ' + g.nombre + ' (' + g.total_integrantes + ')' + headerIcon + '</strong></div>';
                    html += '<div class="card-body p-0"><ul class="list-group list-group-flush">';
                    g.integrantes.forEach(function(integ) {
                        // Lógica: si este integrante es el estudiante actual, resaltar
                        var esYo = (miId && integ.id == miId);
                        var claseLista = esYo ? ' list-group-item-warning font-weight-bold' : ' list-group-item';
                        var badgeHtml = '';
                        if (esYo) {
                            badgeHtml = ' <span class="badge badge-success ml-1"><i class="fas fa-check-circle"></i>Tú</span>';
                        }
                        html += '<li class="list-group-item py-2' + claseLista + '">';
                        html += '<i class="fas fa-user mr-1"></i> ' + integ.apellidos + ', ' + integ.nombres;
                        html += badgeHtml;
                        html += '</li>';
                    });
                    html += '</ul></div></div>';
                });
            }
            body.innerHTML = html;
        })
        .catch(function() { body.innerHTML = '<div class="text-center text-muted py-3">Error al cargar grupos.</div>'; });
}

// Participacion en vivo: consulta si hay participacion activa para mostrar/ocultar el boton
// sin necesidad de recargar la pagina. Se respeta el estado "ya registrado".
$(function() {
    refrescarParticipacionesEstudiante();
    setInterval(refrescarParticipacionesEstudiante, 2000);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
