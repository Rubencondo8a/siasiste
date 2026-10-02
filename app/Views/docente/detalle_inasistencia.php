<?php
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/alerta-inasistencia.css?v=' . filemtime(ROOT_PATH . 'public/css/alerta-inasistencia.css') . '">';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../partials/messages.php';

function nivelAlertaDetalle(float $pct): string
{
    if ($pct >= 60) return 'danger';
    if ($pct >= 45) return 'warning';
    return 'info';
}

$etiquetaEstado = [
    'P' => ['Presente', 'success', 'fa-check'],
    'T' => ['Tardanza',  'warning', 'fa-clock'],
    'A' => ['Ausente',   'danger',  'fa-user-times'],
    'J' => ['Justificado', 'info', 'fa-user-check'],
];
$meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
$diasSemana = ['Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'];

$pct = (float) $detalle['porcentaje'];
$nivel = nivelAlertaDetalle($pct);
$sobreUmbral = $pct > $umbralInasistencia;
?>

<section class="content-header">
    <h1>
        <i class="fas fa-calendar-alt mr-1"></i> Detalle de inasistencia
    </h1>
    <a href="<?= BASE_URL ?>docente/inicio" class="btn btn-secondary btn-sm float-right mt-1">
        <i class="fas fa-arrow-left mr-1"></i> Volver al inicio
    </a>
</section>

<section class="content">

    <!-- Identificacion del alumno y su unidad -->
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="mb-2 mb-md-0">
                            <h3 class="card-title mb-1" style="font-size:1.05rem;">
                                <?= htmlspecialchars($detalle['apellidos'] . ', ' . $detalle['nombres']) ?>
                            </h3>
                            <p class="text-muted mb-0" style="font-size:.85rem;">
                                <?php if (!empty($detalle['dni'])): ?>
                                    <i class="fas fa-id-card mr-1"></i>DNI <?= htmlspecialchars($detalle['dni']) ?>
                                    &nbsp;&nbsp;
                                <?php endif; ?>
                                <i class="fas fa-book mr-1"></i>
                                <?= htmlspecialchars($detalle['curso_nombre']) ?>
                                <?php if (!empty($detalle['curso_codigo'])): ?>
                                    <span class="text-muted">(<?= htmlspecialchars($detalle['curso_codigo']) ?>)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-md-right">
                            <div class="d-flex align-items-center justify-content-md-end">
                                <div class="progress" style="height:10px; width:170px;">
                                    <div class="progress-bar bg-<?= $nivel ?>" role="progressbar"
                                         style="width:<?= min(100, $pct) ?>%"
                                         aria-valuenow="<?= round($pct) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="badge badge-<?= $nivel ?> ml-2" style="font-size:.9rem;">
                                    <?= number_format($pct, 1) ?>%
                                </span>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Ausencias sobre <?= (int) $detalle['total'] ?> clases registradas
                            </small>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <div class="row text-center">
                        <?php
                        $iconos = ['presentes' => 'fa-check', 'tardanzas' => 'fa-clock', 'ausencias' => 'fa-user-times', 'justificados' => 'fa-user-check', 'total' => 'fa-book'];
                        foreach ([['presentes', 'Presentes', 'success'], ['tardanzas', 'Tardanzas', 'warning'], ['ausencias', 'Ausencias', 'danger'], ['justificados', 'Justificados', 'info'], ['total', 'Clases', 'secondary']] as $k): ?>
                            <div class="col-4 col-md-2 offset-md-1 border-right" style="font-size:.78rem;">
                                <div><i class="fas <?= $iconos[$k[0]] ?> text-<?= $k[2] ?> mr-1"></i><?= $k[1] ?></div>
                                <div class="font-weight-bold" style="font-size:1.05rem;"><?= (int) $detalle[$k[0]] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lectura rapida del caso -->
    <?php if ($sobreUmbral): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?= $pct >= 60 ? 'danger' : 'warning' ?> py-2 px-3" style="font-size:.88rem;">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Este alumno supera el <?= number_format($umbralInasistencia, 0) ?>% de inasistencia en esta unidad.
                    <?php if ((int) $detalle['racha_maxima'] >= 3): ?>
                        <br>
                        <strong class="mr-1"><i class="fas fa-fire mr-1"></i>Racha m&aacute;s larga de <?= (int) $detalle['racha_maxima'] ?> clases seguidas</strong>
                        sinkubik ausentes: la inasistencia es sostenida, no puntual.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sesion a sesion -->
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list-ul mr-1"></i> Registro por clase
                    </h3>
                </div>
                <?php if (empty($detalle['sesiones'])): ?>
                    <div class="card-body">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Este alumno no tiene registros de asistencia en esta unidad.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive ai-wrap">
                        <table class="table table-sm table-hover nodt ai-table mb-0" style="font-size:.88rem;">
                            <thead>
                                <tr>
                                    <th style="width:150px;">Fecha</th>
                                    <th style="width:190px;">Horario</th>
                                    <th class="text-center" style="width:120px;">Estado</th>
                                    <th>Motivo registrado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalle['sesiones'] as $s):
                                    $est = $etiquetaEstado[$s['estado']] ?? ['Desconocido', 'secondary', 'fa-question'];
                                    $ts = strtotime($s['fecha']);
                                    $mes = $meses[(int) date('n', $ts)];
                                    $obs = trim((string) ($s['observacion'] ?? ''));
                                ?>
                                    <tr class="ai-dato">
                                        <td data-label="Fecha">
                                            <i class="fas fa-calendar-day text-muted mr-1"></i>
                                            <?= (int) date('j', $ts) ?> de <?= $mes ?>
                                            <span class="text-muted d-block" style="font-size:.75rem;">
                                                <?= $diasSemana[date('l', $ts)] ?? '' ?>
                                            </span>
                                        </td>
                                        <td data-label="Horario" class="text-muted">
                                            <?php if (!empty($s['hora_inicio'])): ?>
                                                <i class="fas fa-clock mr-1"></i>
                                                <?= htmlspecialchars(substr((string) $s['hora_inicio'], 0, 5)) ?>
                                                <?php if (!empty($s['hora_fin'])): ?>
                                                    &ndash; <?= htmlspecialchars(substr((string) $s['hora_fin'], 0, 5)) ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Estado" class="text-center">
                                            <span class="badge badge-<?= $est[1] ?>">
                                                <i class="fas <?= $est[2] ?>"></i> <?= $est[0] ?>
                                            </span>
                                        </td>
                                        <td data-label="Motivo" class="<?= $obs === '' ? 'text-muted' : '' ?>">
                                            <?php if ($obs !== ''): ?>
                                                <i class="fas fa-quote-left text-muted mr-1" style="font-size:.7rem;"></i>
                                                <?= htmlspecialchars($obs) ?>
                                            <?php else: ?>
                                                <span class="text-muted"><em>Sin motivo registrado</em></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer py-2" style="font-size:.8rem;">
                        <i class="fas fa-info-circle mr-1"></i>
                        El motivo solo se registra cuando la inasistencia se marca como <strong>Justificada</strong>.
                        Las ausencias simples no llevan motivo: puedes anotarlo al tomar la asistencia.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
