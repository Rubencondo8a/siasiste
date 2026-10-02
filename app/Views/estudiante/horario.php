<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<?php
$diasSemana = ['Lunes','Martes','Miercoles','Jueves','Viernes'];
$diasCorto  = ['Lunes'=>'Lun','Martes'=>'Mar','Miercoles'=>'Mie','Jueves'=>'Jue','Viernes'=>'Vie'];
$colores = ['primary','success','info','warning','danger','secondary','dark'];
$colorMap = [];
foreach ($horarios as $h) {
    if (!isset($colorMap[$h['curso_id']])) {
        $colorMap[$h['curso_id']] = $colores[count($colorMap) % count($colores)];
    }
}

$horariosPorDia = [];
foreach ($diasSemana as $d) $horariosPorDia[$d] = [];
foreach ($horarios as $h) {
    if (isset($horariosPorDia[$h['dia']])) {
        $horariosPorDia[$h['dia']][] = $h;
    }
}
foreach ($horariosPorDia as &$lista) {
    usort($lista, function($a, $b) { return strcmp($a['hora_inicio'], $b['hora_inicio']); });
}
unset($lista);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/horario-estudiante.css?v=<?= filemtime(ROOT_PATH . 'public/css/horario-estudiante.css') ?>">

<section class="content-header">
    <h1><i class="fas fa-calendar-alt mr-1"></i> Mi Horario de Clases</h1>
</section>

<section class="content">
    <?php if (empty($horarios)): ?>
        <div class="callout callout-info">
            <h5>Sin horarios</h5>
            <p>No hay horarios registrados para sus cursos asignados.</p>
        </div>
    <?php else: ?>

        <?php if (!empty($colorMap)): ?>
        <div class="row mb-3 schedule-desktop">
            <?php foreach ($colorMap as $cursoId => $color): ?>
                <?php
                $cursoInfo = null;
                foreach ($horarios as $h) { if ($h['curso_id'] == $cursoId) { $cursoInfo = $h; break; } }
                ?>
                <div class="col-md-4 col-sm-6 mb-2">
                    <div class="d-flex align-items-center">
                        <span class="badge badge-<?= $color ?> mr-2" style="width:18px;height:18px;padding:0;">&nbsp;</span>
                        <small><strong><?= htmlspecialchars($cursoInfo['curso_nombre']) ?></strong></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        $hours = [];
        foreach ($horarios as $h) {
            $start = (int) date('H', strtotime($h['hora_inicio']));
            $end   = (int) date('H', strtotime($h['hora_fin']));
            for ($hr = $start; $hr < $end; $hr++) {
                if (!in_array($hr, $hours)) $hours[] = $hr;
            }
        }
        sort($hours);
        ?>

        <div class="schedule-desktop">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-bordered nodt" style="table-layout:fixed;">
                        <thead>
                            <tr>
                                <th style="width:70px;" class="text-center">Hora</th>
                                <?php foreach ($diasSemana as $d): ?>
                                    <th class="text-center" style="min-width:120px;"><?= $d ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hours as $hr): ?>
                                <tr>
                                    <td class="hour-label align-middle"><strong><?= str_pad($hr, 2, '0', STR_PAD_LEFT) ?>:00</strong></td>
                                    <?php foreach ($diasSemana as $d): ?>
                                        <td class="schedule-cell">
                                            <?php foreach ($horarios as $h):
                                                if ($h['dia'] !== $d) continue;
                                                $hStart = (int) date('H', strtotime($h['hora_inicio']));
                                                if ($hStart !== $hr) continue;
                                                $color = $colorMap[$h['curso_id']] ?? 'secondary';
                                            ?>
                                                <div class="schedule-block bg-<?= $color ?>" onclick="showCourseInfo(<?= $h['curso_id'] ?>)">
                                                    <strong><?= htmlspecialchars($h['curso_nombre']) ?></strong><br>
                                                    <small><?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></small><br>
                                                    <?php if ($h['ambiente_nombre']): ?>
                                                        <small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($h['ambiente_nombre']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="schedule-mobile" id="scheduleMobile">
            <div class="day-tabs" id="dayTabs">
                <?php foreach ($diasSemana as $i => $d): ?>
                    <?php $count = count($horariosPorDia[$d]); ?>
                    <div class="day-tab <?= $i === 0 ? 'active' : '' ?>" data-day="<?= $d ?>" onclick="switchDay('<?= $d ?>', this)">
                        <?= $diasCorto[$d] ?>
                        <?php if ($count > 0): ?>
                            <span class="day-count"><?= $count ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php foreach ($diasSemana as $i => $d): ?>
                <div class="day-panel <?= $i === 0 ? 'active' : '' ?>" id="panel-<?= $d ?>">
                    <?php if (empty($horariosPorDia[$d])): ?>
                        <div class="empty-day">
                            <i class="fas fa-coffee"></i>
                            Sin clases este día
                        </div>
                    <?php else: ?>
                        <?php foreach ($horariosPorDia[$d] as $h):
                            $color = $colorMap[$h['curso_id']] ?? 'secondary';
                        ?>
                            <div class="mobile-card bg-<?= $color ?>" onclick="showCourseInfo(<?= $h['curso_id'] ?>)">
                                <div class="mc-time">
                                    <i class="far fa-clock"></i>
                                    <?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?> — <?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?>
                                </div>
                                <div class="mc-course"><?= htmlspecialchars($h['curso_nombre']) ?></div>
                                <?php if ($h['ambiente_nombre']): ?>
                                    <div class="mc-ambient"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($h['ambiente_nombre']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</section>

<div class="modal fade" id="courseInfoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="fas fa-info-circle mr-1"></i> Detalle del Curso</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="courseInfoBody">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
function switchDay(day, tab) {
    document.querySelectorAll('#scheduleMobile .day-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('#scheduleMobile .day-panel').forEach(function(p) { p.classList.remove('active'); });
    tab.classList.add('active');
    var panel = document.getElementById('panel-' + day);
    if (panel) panel.classList.add('active');
}
function showCourseInfo(cursoId) {
    var body = document.getElementById('courseInfoBody');
    body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';
    $('#courseInfoModal').modal('show');
    fetch('<?= BASE_URL ?>estudiante/informacionCurso?curso_id=' + cursoId)
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
