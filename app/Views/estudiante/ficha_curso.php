<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>
        <i class="fas fa-book mr-1"></i> <?= htmlspecialchars($curso['nombre']) ?>
        <small class="text-muted"><?= htmlspecialchars($curso['codigo']) ?></small>
    </h1>
</section>

<section class="content">

    <div class="row mb-3">
        <div class="col-md-6">
            <a href="<?= BASE_URL ?>estudiante/misCursos" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver a Mis Cursos
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-graduation-cap"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Ciclo</span>
                    <span class="info-box-number"><?= htmlspecialchars($curso['ciclo']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-calendar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Periodo</span>
                    <span class="info-box-number" style="font-size:.9rem;"><?= htmlspecialchars($curso['periodo_nombre']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-chalkboard-teacher"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Docente</span>
                    <span class="info-box-number" style="font-size:.8rem;">
                        <?= $curso['docente_nombres'] ? htmlspecialchars($curso['docente_apellidos'] . ', ' . $curso['docente_nombres']) : '—' ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="info-box <?= $promedioGeneral >= 10 ? 'bg-success' : 'bg-danger' ?>">
                <span class="info-box-icon" style="color:rgba(255,255,255,.8);"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color:rgba(255,255,255,.9);">Promedio del Semestre</span>
                    <span class="info-box-number" style="color:#fff;"><?= number_format($promedioGeneral, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($semanasCalificadas) && !empty($chartLabels)): ?>
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Evolución de Notas</h3>
            </div>
            <div class="card-body">
                <canvas id="gradesChart" style="height:250px;"></canvas>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($semanasCalificadas)): ?>
        <div class="callout callout-info">
            <h5>Sin notas registradas</h5>
            <p>Aún no se han registrado calificaciones para este curso.</p>
        </div>
    <?php else: ?>
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-1"></i> Notas por Semana</h3>
            </div>
            <div class="card-body p-0 overflow-hidden">
                <table class="table table-hover nodt mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;" class="text-center">#</th>
                            <th>Semana</th>
                            <th class="text-center" style="width:120px;">Fechas</th>
                            <th class="text-center" style="width:100px;">Promedio</th>
                            <th class="text-center" style="width:80px;">Estado</th>
                            <th class="text-center" style="width:70px;">Debates</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semanasCalificadas as $s): ?>
                            <tr style="cursor:pointer;" onclick="window.location.href='<?= BASE_URL ?>estudiante/verSemana?semana_id=<?= $s['id'] ?>'">
                                <td class="text-center align-middle"><strong><?= $s['numero_semana'] ?></strong></td>
                                <td class="align-middle"><?= htmlspecialchars($s['nombre']) ?></td>
                                <td class="text-center align-middle" style="font-size:.8rem;">
                                    <?= date('d/m', strtotime($s['fecha_inicio'])) ?> - <?= date('d/m', strtotime($s['fecha_fin'])) ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($s['promedio'] !== null): ?>
                                        <strong style="font-size:1rem;"><?= number_format($s['promedio'], 2) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($s['promedio'] !== null): ?>
                                        <?php if ($s['promedio'] >= 10): ?>
                                            <span class="badge badge-success">Aprobado</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Reprobado</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Sin nota</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <a href="<?= BASE_URL ?>estudiante/discusiones?semana_id=<?= $s['id'] ?>" class="btn btn-outline-primary btn-sm" title="Debates de la semana" onclick="event.stopPropagation()">
                                        <i class="fas fa-comments"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</section>

<?php
$inlineScripts = ['$(document).ready(function() {
    var ctx = document.getElementById("gradesChart");
    if (!ctx) return;
    ctx = ctx.getContext("2d");
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ' . json_encode($chartLabels) . ',
            datasets: [{
                label: "Promedio",
                data: ' . json_encode($chartData) . ',
                backgroundColor: ' . json_encode(array_map(fn($v) => $v >= 13 ? 'rgba(13,110,253,.7)' : 'rgba(220,53,69,.7)', $chartData)) . ',
                borderColor: ' . json_encode(array_map(fn($v) => $v >= 13 ? '#0d6efd' : '#dc3545', $chartData)) . ',
                borderWidth: 2,
                borderRadius: 4,
                maxBarThickness: 50,
                order: 2
            }, {
                label: "Evolución",
                type: "line",
                data: ' . json_encode($chartData) . ',
                borderColor: "#0d6efd",
                backgroundColor: "rgba(13,110,253,.08)",
                borderWidth: 2,
                pointBackgroundColor: ' . json_encode(array_map(fn($v) => $v >= 13 ? '#0d6efd' : '#dc3545', $chartData)) . ',
                pointBorderColor: "#fff",
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
                y: {
                    beginAtZero: true,
                    max: 20,
                    ticks: { stepSize: 2 }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return ctx.parsed.y.toFixed(2); }
                    }
                }
            }
        },
        plugins: [{
            id: "customLabels",
            afterDraw: function(chart) {
                var c = chart.ctx;
                chart.data.datasets[0].data.forEach(function(value, i) {
                    if (value === null) return;
                    var meta = chart.getDatasetMeta(0);
                    var bar = meta.data[i];
                    if (!bar || !bar.x || !bar.y) return;
                    c.fillStyle = "#333";
                    c.font = "bold 11px Arial";
                    c.textAlign = "center";
                    c.textBaseline = "bottom";
                    c.fillText(value.toFixed(2), bar.x, bar.y - 3);
                });
            }
        }]
    });
});'];
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
