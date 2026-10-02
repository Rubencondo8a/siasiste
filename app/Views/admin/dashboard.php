<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>Panel de Administración</h1>
</section>

<section class="content">
    <?php
    $statCards = [
        [
            'icon'  => 'fas fa-chalkboard-teacher',
            'value' => (string) ($totalDocentes ?? 0),
            'label' => 'Docentes',
            'url'   => BASE_URL . 'admin/usuarios',
            'color' => '#7C6CF5', 'glow' => 'rgba(124,108,245,.35)',
        ],
        [
            'icon'  => 'fas fa-user-graduate',
            'value' => (string) ($totalEstudiantes ?? 0),
            'label' => 'Estudiantes',
            'url'   => BASE_URL . 'admin/estudiantes',
            'color' => '#2563EB', 'glow' => 'rgba(37,99,235,.35)',
        ],
        [
            'icon'  => 'fas fa-graduation-cap',
            'value' => (string) ($totalCarreras ?? 0),
            'label' => 'Carreras',
            'url'   => BASE_URL . 'admin/carreras',
            'color' => '#0FA97A', 'glow' => 'rgba(15,169,122,.35)',
        ],
        [
            'icon'  => 'fas fa-book-open',
            'value' => (string) ($totalCursos ?? 0),
            'label' => 'Cursos',
            'url'   => BASE_URL . 'admin/cursos',
            'color' => '#E63777', 'glow' => 'rgba(230,55,119,.35)',
        ],
    ];
    ?>

    <div class="row">
        <?php foreach ($statCards as $c): ?>
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

    <?php if (!empty($periodoActivo)): ?>
    <?php
    $periodo = $periodoActivo;
    $inicio  = new DateTime($periodo['fecha_inicio']);
    $fin     = new DateTime($periodo['fecha_fin']);
    $hoy     = new DateTime();
    $totalDias = max(1, (int) $fin->diff($inicio)->days);
    $transcurridos = max(0, min($totalDias, (int) $hoy->diff($inicio)->days));
    $progreso = (int) round(($transcurridos / $totalDias) * 100);
    $diasRestantes = max(0, (int) $fin->diff($hoy)->days);
    ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <h3 class="card-title"><i class="fas fa-calendar-check text-success mr-2"></i>Resumen del Periodo Activo</h3>
                    <span class="badge badge-success px-3 py-2"><?= htmlspecialchars($periodo['nombre']) ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <i class="fas fa-flag-checkered text-muted mr-1"></i>
                                <strong>Inicio:</strong>
                                <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-flag text-muted mr-1"></i>
                                <strong>Fin:</strong>
                                <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                            </div>
                            <div class="mb-3">
                                <i class="far fa-clock text-muted mr-1"></i>
                                <strong>Días restantes:</strong> <?= $diasRestantes ?>
                            </div>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                     style="width: <?= $progreso ?>%"><?= $progreso ?>%</div>
                            </div>
                            <small class="text-muted">Progreso del periodo académico</small>
                        </div>
                        <div class="col-md-4">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <div class="h3 mb-1 text-primary font-weight-bold"><?= (int) $totalCursos ?></div>
                                        <div class="text-muted small">Cursos activos</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <div class="h3 mb-1 text-success font-weight-bold"><?= (int) $semanasPeriodo ?></div>
                                        <div class="text-muted small">Semanas creadas</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <div class="h3 mb-1 text-warning font-weight-bold"><?= (int) $totalDocentes ?></div>
                                        <div class="text-muted small">Docentes</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <div class="h3 mb-1 text-info font-weight-bold"><?= (int) $totalEstudiantes ?></div>
                                        <div class="text-muted small">Estudiantes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php if (!empty($cursosPorCarrera)): ?>
                            <h6 class="text-muted mb-2"><i class="fas fa-chart-pie mr-1"></i>Cursos por carrera</h6>
                            <canvas id="chartCursosCarrera" height="170"></canvas>
                            <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-chart-pie fa-3x mb-3 d-block"></i>
                                Sin cursos registrados en este periodo.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                No hay un periodo académico activo.
                <a href="<?= BASE_URL ?>admin/periodos" class="alert-link">Ir a Periodos</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php
$inlineScripts = $inlineScripts ?? [];
if (!empty($cursosPorCarrera)) {
    $labels = array_column($cursosPorCarrera, 'carrera');
    $data   = array_map('intval', array_column($cursosPorCarrera, 'total'));
    $labelsJson = json_encode($labels, JSON_UNESCAPED_UNICODE);
    $dataJson   = json_encode($data);
    $inlineScripts[] = <<<JS
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') return;
        new Chart(document.getElementById('chartCursosCarrera'), {
            type: 'doughnut',
            data: {
                labels: {$labelsJson},
                datasets: [{
                    data: {$dataJson},
                    backgroundColor: ['#2563EB', '#0FA97A', '#E63777', '#F59E0B', '#7C6CF5', '#14B8A6', '#EF4444', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    });
    JS;
}
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
