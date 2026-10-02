<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php
$flashTA = getFlash();
$inlineScripts = $inlineScripts ?? []; ?>
<?php
$cfgFlash = [
    'success' => ['alert-success', 'fa-check-circle', 'Asistencia registrada'],
    'error'   => ['alert-danger',  'fa-times-circle',   'No se pudo registrar la asistencia'],
    'warning' => ['alert-warning','fa-exclamation-triangle', 'Revise lo siguiente'],
    'info'    => ['alert-info',   'fa-info-circle',   'Aviso'],
];
$flashTA = !empty($flashTA) ? $flashTA : null;
if ($flashTA && !isset($cfgFlash[$flashTA['type']])) {
    $flashTA['type'] = 'info';
}
?>

<section class="content-header">
    <div class="ta-header">
        <div class="ta-header-title">
            <h1 class="ta-title"><i class="fas fa-user-check mr-1"></i> Tomar Asistencia</h1>
            <span class="ta-chip ta-chip-primary"><?= htmlspecialchars($curso['nombre']) ?> · <?= htmlspecialchars($curso['codigo']) ?></span>
            <span class="ta-chip ta-chip-ghost">Ciclo <?= htmlspecialchars($curso['ciclo']) ?></span>
        </div>
        <div class="ta-header-actions">
            <a href="<?= BASE_URL ?>docente/asistencias" class="btn btn-sm ta-btn ta-btn-ghost" title="Volver a la lista de asistencias">
                <i class="fas fa-arrow-left mr-1"></i><span class="d-none d-sm-inline">Volver</span>
            </a>
            <a href="<?= BASE_URL ?>docente/reporteAsistencia?curso_id=<?= $curso['id'] ?>" class="btn btn-sm ta-btn ta-btn-ghost" title="Ver el reporte de asistencia del curso">
                <i class="fas fa-chart-bar mr-1"></i><span class="d-none d-sm-inline">Reporte</span>
            </a>
        </div>
    </div>
</section>

<section class="content">
    <?php if ($flashTA): ?>
        <?php $cfg = $cfgFlash[$flashTA['type']]; ?>
        <div class="alert <?= $cfg[0] ?> alert-dismissible fade show" id="avisoAsistencia" role="alert">
            <h5 class="mb-1" style="font-size:.95rem;font-weight:700;">
                <i class="fas <?= $cfg[1] ?> mr-1"></i><?= $cfg[2] ?>
            </h5>
            <p class="mb-0" style="font-size:.86rem;"><?= htmlspecialchars($flashTA['message']) ?></p>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (empty($estudiantes)): ?>
        <div class="callout callout-warning mb-0">
            <h5>Sin estudiantes</h5>
            <p>No hay estudiantes asignados a este curso. Agregue estudiantes desde
                <a href="<?= BASE_URL ?>docente/estudiantesCurso?curso_id=<?= $curso['id'] ?>">Mis Cursos</a>.
            </p>
        </div>
    <?php else: ?>

    <?php
    $marcados = 0;
    $pendientes = 0;
    foreach ($estudiantes as $est) {
        if (isset($existentes[(int) $est['id']])) {
            $marcados++;
        } else {
            $pendientes++;
        }
    }
    $minTol = (int) ($tolerancia['tolerancia_minutos'] ?? 0);
    ?>

    <div class="ta-layout">
        <div class="ta-layout-main">

            <?php if (!$coincideConHorario): ?>
                <div class="ta-alert ta-alert-warn" id="avisoSinClase">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <span>
                        <strong>Este día no hay clase programada para este curso.</strong>
                        <span class="d-none d-md-inline">Horario del curso:
                            <?php
                            $bloques = [];
                            foreach ($horarios as $h) {
                                $bloques[] = htmlspecialchars($h['dia']) . ' ' . substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5);
                            }
                            echo $bloques ? implode(' · ', $bloques) : 'sin definir';
                            ?>. Marque <strong>Clase de recuperación</strong> para poder guardar.
                        </span>
                    </span>
                </div>
            <?php endif; ?>

            <div class="ta-toolbar">
                <form method="GET" action="<?= BASE_URL ?>docente/tomarAsistencia" class="d-inline-flex align-items-center">
                    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                    <input type="hidden" name="horario_id" value="<?= (int) $horario_id ?>">
                    <label for="fecha" class="ta-label mr-2 mb-0">Fecha</label>
                    <input type="date" class="form-control form-control-sm ta-date" id="fecha" name="fecha"
                           value="<?= htmlspecialchars($fecha) ?>" required onchange="this.form.submit()"
                           title="Cambiar la fecha para ver o editar la asistencia de otro día">
                </form>
                <span class="ta-day"><?= htmlspecialchars($diaNombre) ?></span>
                <span class="ta-sep d-none d-md-inline-block"></span>
                <div class="ta-field">
                    <span class="ta-label">Clase</span>
                    <?php if (!empty($bloquesDia)): ?>
                        <div class="ta-classes">
                            <?php foreach ($bloquesDia as $b): ?>
                                <?php $bid = (int) $b['id']; ?>
                                <a href="<?= BASE_URL ?>docente/tomarAsistencia?curso_id=<?= $curso['id'] ?>&fecha=<?= urlencode($fecha) ?>&horario_id=<?= $bid ?>"
                                   class="ta-chip <?= $bid === $horario_id ? 'ta-chip-active' : 'ta-chip-ghost' ?>"
                                   title="Clase de <?= substr($b['hora_inicio'], 0, 5) ?> a <?= substr($b['hora_fin'], 0, 5) ?>">
                                    <?= substr($b['hora_inicio'], 0, 5) ?>–<?= substr($b['hora_fin'], 0, 5) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="ta-chip ta-chip-ghost">Sin clase este día</span>
                    <?php endif; ?>
                </div>
                <div class="ta-spacer"></div>
                <div class="ta-stats">
                    <span class="ta-stat" id="badgeSinRegistros" <?= $marcados > 0 ? 'style="display:none;"' : '' ?>>Sin registros</span>
                    <span class="ta-stat ta-stat-ok" id="badgeMarcados" <?= $marcados > 0 ? '' : 'style="display:none;"' ?>>
                        <i class="fas fa-check-circle"></i><span id="countMarcados"><?= $marcados ?></span> marcaron
                    </span>
                    <span class="ta-stat ta-stat-warn" id="badgePendientes" <?= $pendientes > 0 ? '' : 'style="display:none;"' ?>>
                        <i class="fas fa-hourglass-half"></i><span id="countPendientes"><?= $pendientes ?></span> pendientes
                    </span>
                </div>
            </div>

            <form method="POST" action="<?= BASE_URL ?>docente/guardarAsistencia" id="formAsistencia">
                <?= csrfField() ?>
                <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                <input type="hidden" name="horario_id" value="<?= (int) $horario_id ?>">

                <div class="ta-filterbar">
                    <div class="btn-group btn-group-sm ta-filter" role="group" aria-label="Filtrar por marca de asistencia">
                        <button type="button" class="btn btn-outline-primary active" data-filtro="todos"
                                title="Mostrar todos los estudiantes">Todos</button>
                        <button type="button" class="btn btn-outline-success" data-filtro="marcados"
                                title="Solo estudiantes que ya tienen registro guardado">Marcados</button>
                        <button type="button" class="btn btn-outline-warning" data-filtro="pendientes"
                                title="Solo estudiantes sin registro guardado todavía">Pendientes</button>
                    </div>
                    <button type="button" class="btn btn-sm ta-btn ta-btn-ghost" id="btnMarcarTodos"
                            title="Marcar a todos los estudiantes pendientes como Presente (respetando la tolerancia de llegada)">
                        <i class="fas fa-check-double mr-1"></i>Marcar todos
                    </button>
                </div>

                <div class="ta-table-wrap">
                    <table class="ta-table">
                        <thead>
                            <tr>
                                <th class="ta-num">#</th>
                                <th>Estudiante</th>
                                <th class="text-center ta-col-marca">Marca</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $i => $est): ?>
                                <?php
                                $sid = (int) $est['id'];
                                $reg = $existentes[$sid] ?? null;
                                $valor = $reg['estado'] ?? null;
                                $obsVal = $reg['observacion'] ?? '';
                                $estadosMap = [
                                    'P' => ['label' => 'Presente',    'abbr' => 'P', 'css' => 'as-ok'],
                                    'T' => ['label' => 'Tardanza',    'abbr' => 'T', 'css' => 'as-warn'],
                                    'A' => ['label' => 'Ausente',     'abbr' => 'A', 'css' => 'as-no'],
                                    'J' => ['label' => 'Justificado', 'abbr' => 'J', 'css' => 'as-just'],
                                ];
                                ?>
                                <tr data-marcado="<?= isset($existentes[$sid]) ? '1' : '0' ?>">
                                    <td class="ta-num"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between ta-name">
                                            <strong><?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?></strong>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info ml-2 ta-btn-face"
                                                    title="<?= isset($estudiantesConRostro[$sid]) ? 'Rostro registrado. Hacer clic para actualizarlo.' : 'Registrar el rostro de este estudiante' ?>"
                                                    onclick="AdminModals.openModal('<?= BASE_URL ?>docente/formularioRostro?curso_id=<?= $curso['id'] ?>&estudiante_id=<?= $sid ?>', 'Registrar Rostro', 'lg')">
                                                <i class="fas <?= isset($estudiantesConRostro[$sid]) ? 'fa-check-circle text-success' : 'fa-id-card' ?>"></i>
                                                <span class="d-none d-md-inline">Rostro</span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center ta-col-marca">
                                        <?php if (isset($existentes[$sid])): ?>
                                            <span class="badge badge-success asistencia-badge"><i class="fas fa-check-circle mr-1"></i>Marcado</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning asistencia-badge"><i class="fas fa-hourglass-half mr-1"></i>Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="asistencia-picker" data-student="<?= $sid ?>">
                                            <button type="button"
                                                    class="as-btn as-pend <?= $valor === null ? 'active' : '' ?>"
                                                    data-estado="pendiente"
                                                    title="Pendiente (aún no marcado)">
                                                <strong class="as-abbr"><i class="fas fa-hourglass-half"></i></strong>
                                            </button>
                                            <?php foreach ($estadosMap as $clave => $info): ?>
                                                <button type="button"
                                                        class="as-btn <?= $info['css'] ?> <?= $valor === $clave ? 'active' : '' ?>"
                                                        data-estado="<?= $clave ?>"
                                                        title="<?= $info['label'] ?>">
                                                    <strong class="as-abbr"><?= $info['abbr'] ?></strong>
                                                </button>
                                            <?php endforeach; ?>
                                            <input type="hidden" name="estados[<?= $sid ?>]" value="<?= htmlspecialchars($valor ?? 'pendiente') ?>">
                                        </div>
                                        <div class="asistencia-obs <?= $valor === 'J' ? '' : 'd-none' ?>"
                                             style="max-width:320px;margin-top:6px;">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-comment-medical"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="observaciones[<?= $sid ?>]"
                                                       maxlength="255" placeholder="Motivo de la justificación"
                                                       value="<?= htmlspecialchars($obsVal) ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ta-actions">
                    <?php if (!$coincideConHorario): ?>
                        <label class="ta-recu d-inline-flex align-items-center mb-0 mr-3">
                            <input type="checkbox" id="checkRecuperacion" name="recuperacion" value="1" class="mr-1">
                            <span><i class="fas fa-calendar-plus mr-1"></i>Clase de recuperación</span>
                        </label>
                    <?php endif; ?>
                    <div class="ta-spacer"></div>
                    <button type="submit" class="btn btn-success btn-sm ta-btn ta-btn-save" id="btnGuardarAsistencia"
                            title="Guardar los estados de asistencia de todos los estudiantes">
                        <i class="fas fa-save mr-1"></i>Guardar Asistencia
                    </button>
                </div>
            </form>
        </div>

        <aside class="ta-layout-aside">
            <div class="ta-panel">
                <div class="ta-panel-head" data-toggle="collapse" data-target="#taFacialBody" aria-expanded="true" role="button">
                    <span><i class="fas fa-id-card mr-1"></i> Reconocimiento facial</span>
                    <i class="fas fa-chevron-down ta-chev"></i>
                </div>
                <div id="taFacialBody" class="collapse show ta-panel-body">
                    <div class="d-flex align-items-center" style="gap:6px;margin-bottom:6px;">
                        <span class="badge badge-light" id="faceCountBadge">0 detectados</span>
                        <span class="badge <?= count($rostrosCurso) > 0 ? 'badge-success' : 'badge-warning' ?>">
                            <?= count($rostrosCurso) ?> rostros
                        </span>
                    </div>
                    <div class="position-relative ta-video" style="background:#000;border-radius:8px;overflow:hidden;">
                        <video id="attendanceVideo" autoplay muted playsinline
                               style="width:100%;max-height:300px;object-fit:cover;"></video>
                        <canvas id="attendanceOverlay" class="position-absolute"
                                style="top:0;left:0;width:100%;height:100%;"></canvas>
                        <button type="button" id="btnToggleCamera" class="btn btn-sm btn-light position-absolute"
                                style="top:8px;right:8px;z-index:5;display:none;border-radius:50%;width:34px;height:34px;padding:0;"
                                title="Cambiar cámara">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div id="attendanceFrame">
                            <span class="corner tl"></span>
                            <span class="corner tr"></span>
                            <span class="corner bl"></span>
                            <span class="corner br"></span>
                            <div id="attendanceHint">Coloque el rostro dentro del recuadro</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-success btn-block ta-btn ta-btn-detectar mt-2" id="btnIniciarFace" style="display:none;"
                            title="Encender la cámara y detectar automáticamente los rostros registrados">
                        <i class="fas fa-video mr-1"></i>Detectar
                    </button>
                    <button type="button" class="btn btn-sm btn-danger btn-block ta-btn ta-btn-detectar mt-2" id="btnDetenerFace" style="display:none;"
                            title="Detener la cámara y el reconocimiento facial">
                        <i class="fas fa-stop mr-1"></i>Detener
                    </button>
                    <div id="attendanceStatus" class="ta-status">Cargando modelos de reconocimiento...</div>
                    <?php if (count($rostrosCurso) === 0): ?>
                        <div class="ta-status ta-status-warn">
                            <i class="fas fa-user-plus mr-1"></i>Aún no hay rostros registrados en este curso.
                            Use el botón <strong>Rostro</strong> de cada estudiante para capturar su rostro.
                        </div>
                    <?php endif; ?>
                    <div id="ultimaDeteccion" class="ta-status">Sin detecciones aún.</div>
                </div>
            </div>

            <div class="ta-panel">
                <div class="ta-panel-head" data-toggle="collapse" data-target="#taTolBody" aria-expanded="false" role="button">
                    <span><i class="fas fa-hourglass-half mr-1"></i> Tolerancia de llegada</span>
                    <i class="fas fa-chevron-down ta-chev"></i>
                </div>
                <div id="taTolBody" class="collapse ta-panel-body">
                    <div class="row no-gutters ta-tol-row">
                        <div class="col-6 pr-1">
                            <label class="ta-label d-block" for="toleranciaHora">Hora inicio</label>
                            <input type="time" class="form-control form-control-sm" id="toleranciaHora"
                                   value="<?= htmlspecialchars(substr((string) ($horaInicioEfectiva ?? ''), 0, 5)) ?>">
                        </div>
                        <div class="col-6 pl-1">
                            <label class="ta-label d-block" for="toleranciaMinutos">Tol. (min)</label>
                            <input type="number" class="form-control form-control-sm" id="toleranciaMinutos"
                                   min="0" max="600" step="5" value="<?= $minTol ?>">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary btn-block mt-2" id="btnGuardarTolerancia"
                            title="Guardar la hora de inicio y la tolerancia en minutos para esta clase">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                    <div class="ta-status" id="toleranciaHoraHint">
                        <?php if ($bloqueActivo): ?>
                            Hora de inicio del horario (<?= htmlspecialchars($diaNombre) ?>): <strong><?= substr($bloqueActivo['hora_inicio'], 0, 5) ?></strong>.
                        <?php elseif (!empty($horaInicioConfigurada)): ?>
                            Hora de inicio configurada manualmente.
                        <?php else: ?>
                            No hay clase programada este día.
                        <?php endif; ?>
                        Quien se marque después de la hora de inicio + tolerancia se registrará como <strong>Tardanza</strong> automáticamente.
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <?php endif; ?>
</section>

<link rel="stylesheet" href="<?= BASE_URL ?>css/tomar-asistencia.css?v=<?= filemtime(ROOT_PATH . 'public/css/tomar-asistencia.css') ?>">

<?php
$inlineScripts[] = str_replace(
    ['__HORA_INICIO__', '__MIN_TOLERANCIA__'],
    [json_encode($horaInicioEfectiva ?? null), (string) (int) ($tolerancia['tolerancia_minutos'] ?? 0)],
    (<<<'JS'
(function() {
    var tolerancia = {
        horaInicio: __HORA_INICIO__,
        minutos: __MIN_TOLERANCIA__
    };

    function horaSuperaLimite() {
        if (!tolerancia.horaInicio) return false;
        if (tolerancia.minutos <= 0) return false;
        var partes = tolerancia.horaInicio.split(':');
        var ahora = new Date();
        var limiteMin = parseInt(partes[0], 10) * 60 + parseInt(partes[1], 10) + parseInt(tolerancia.minutos, 10);
        var actualMin = ahora.getHours() * 60 + ahora.getMinutes();
        return actualMin > limiteMin;
    }

    window.__aplicarTolerancia = function(estado) {
        if (estado === 'P' && horaSuperaLimite()) {
            return 'T';
        }
        return estado;
    };

    window.__marcarEstudiante = function(studentId, estado) {
        var $picker = $('.asistencia-picker[data-student="' + studentId + '"]');
        if (!$picker.length) return;
        var $hidden = $picker.find('input[type=hidden]');
        var $obs = $picker.siblings('.asistencia-obs');
        var finalEstado = estado;
        $hidden.val(finalEstado);
        $picker.find('.as-btn').removeClass('active');
        $picker.find('.as-btn[data-estado="' + finalEstado + '"]').addClass('active');
        if ($obs) $obs.toggleClass('d-none', finalEstado !== 'J');
        var $tr = $picker.closest('tr');
        if ($tr.length) {
            var marcado = finalEstado !== 'pendiente';
            $tr.attr('data-marcado', marcado ? '1' : '0');
            var $badge = $tr.find('.asistencia-badge');
            if ($badge.length) {
                $badge.attr('class', 'badge ' + (marcado ? 'badge-success' : 'badge-warning') + ' asistencia-badge');
                $badge.html(marcado
                    ? '<i class="fas fa-check-circle mr-1"></i>Marcado'
                    : '<i class="fas fa-hourglass-half mr-1"></i>Pendiente');
            }
            $tr.css('background', marcado ? 'rgba(40,167,69,.08)' : '');
        }
        if (window.__recalcularAsistenciaUI) window.__recalcularAsistenciaUI();
    };

    window.__recalcularAsistenciaUI = function() {
        var m = 0, p = 0;
        $('tr[data-marcado]').each(function () {
            if ($(this).data('marcado') === 1) m++; else p++;
        });
        var $cm = $('#countMarcados'), $cp = $('#countPendientes');
        if ($cm.length) $cm.text(m);
        if ($cp.length) $cp.text(p);
        var $bm = $('#badgeMarcados'), $bp = $('#badgePendientes'), $bs = $('#badgeSinRegistros');
        if ($bm.length) $bm.toggle(m > 0);
        if ($bp.length) $bp.toggle(p > 0);
        if ($bs.length) $bs.toggle(m === 0);
    };

    $('.asistencia-picker').each(function () {
        var $wrap = $(this);
        var $obs = $wrap.siblings('.asistencia-obs');
        var toggleObs = function () {
            $obs.toggleClass('d-none', $wrap.find('input[type=hidden]').val() !== 'J');
        };
        $wrap.find('.as-btn').on('click', function () {
            window.__marcarEstudiante($wrap.data('student'), $(this).data('estado'));
        });
        toggleObs();
    });
})();
JS
    )
);

$inlineScripts[] = str_replace(
    ['__CURSO_ID__', '__HORA_INICIO__', '__MIN_TOLERANCIA__'],
    [(string) (int) $curso['id'], json_encode($horaInicioEfectiva ?? null), (string) (int) ($tolerancia['tolerancia_minutos'] ?? 0)],
    (<<<'JS'
(function() {
    var cursoId = __CURSO_ID__;
    var rostrosCurso = [];
    var faceReady = false;
    var deteccionActiva = false;
    var stream = null;
    var timer = null;

    var video = document.getElementById('attendanceVideo');
    var overlay = document.getElementById('attendanceOverlay');
    var frameEl = document.getElementById('attendanceFrame');
    var statusEl = document.getElementById('attendanceStatus');
    var btnIniciar = document.getElementById('btnIniciarFace');
    var btnDetener = document.getElementById('btnDetenerFace');
    var badge = document.getElementById('faceCountBadge');
    var btnToggle = document.getElementById('btnToggleCamera');
    var facingMode = 'user';
    var ultimaDet = document.getElementById('ultimaDeteccion');
    var marcados = {};

    if (!video || !btnIniciar) return;

    var audioCtx = null;

    function notificar(tipo, mensaje) {
        if (statusEl) statusEl.textContent = mensaje;
        if (window.Swal) {
            Swal.fire({
                icon: tipo,
                text: mensaje,
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3200,
                timerProgressBar: true
            });
        }
    }

    function beep() {
        try {
            if (!audioCtx) {
                var AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return;
                audioCtx = new AC();
            }
            if (audioCtx.state === 'suspended') audioCtx.resume();
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.4);
        } catch (e) {}
    }

    function anunciar(nombre, hora, estado) {
        beep();
        try {
            if (!('speechSynthesis' in window)) return;
            var estadoTxt = estado === 'T' ? ', tardanza' : '';
            var msg = new SpeechSynthesisUtterance(
                nombre + ' registrado a las ' + hora + estadoTxt
            );
            msg.lang = 'es-ES';
            msg.rate = 1;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(msg);
        } catch (e) {}
    }

    function mostrarConfirmacion(nombre, hora, estado) {
        var titulo = '✓ ' + nombre + ' registrado';
        var texto = 'Hora: ' + hora;
        if (estado === 'T') {
            texto += ' — TARDANZA';
        }
        if (window.Swal) {
            Swal.fire({
                icon: estado === 'T' ? 'warning' : 'success',
                title: titulo,
                text: texto,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        }
    }

    function marcarPresente(studentId, nombre) {
        if (marcados[studentId]) return;
        marcados[studentId] = true;
        var estado = window.__aplicarTolerancia('P');
        if (window.__marcarEstudiante) {
            window.__marcarEstudiante(studentId, estado);
        }
        var $picker = $('.asistencia-picker[data-student="' + studentId + '"]');
        var $tr = $picker.closest('tr');
        if ($tr.length) {
            $tr.css('background', 'rgba(40,167,69,.12)');
        }
        var n = Object.keys(marcados).length;
        var ahora = new Date();
        var horaStr = ahora.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        badge.textContent = n + ' detectados';
        ultimaDet.textContent = 'Detectado: ' + nombre + ' (' + horaStr + ')' +
                (estado === 'T' ? ' - TARDANZA' : '');
        mostrarConfirmacion(nombre, horaStr, estado);
        anunciar(nombre, horaStr, estado);
    }

    function obtenerRostros() {
        return $.ajax({
            url: window.BASE_URL + 'docente/obtenerRostrosCurso?curso_id=' + cursoId,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    }

    function cargarModelos() {
        notificar('info', 'Cargando modelos de reconocimiento...');
        var loaders = [
            faceapi.nets.tinyFaceDetector.loadFromUri(window.BASE_URL + 'models'),
            faceapi.nets.faceLandmark68Net.loadFromUri(window.BASE_URL + 'models'),
            faceapi.nets.faceRecognitionNet.loadFromUri(window.BASE_URL + 'models')
        ];
        return Promise.all(loaders);
    }

    function iniciar() {
        if (deteccionActiva) return;
        notificar('info', 'Consultando rostros registrados...');
        obtenerRostros().done(function(res) {
            if (!res.success) {
                notificar('error', res.message || 'Error al obtener rostros.');
                return;
            }
            rostrosCurso = (res.data || []).map(function(r) {
                r.descriptor = new Float32Array(r.descriptor);
                return r;
            });
            notificar('success', rostrosCurso.length + ' rostros disponibles para el curso.');

            if (rostrosCurso.length === 0) {
                notificar('warning', 'Ningún estudiante tiene rostro registrado. Regístrelos antes de usar el reconocimiento.');
                btnDetener.style.display = 'none';
                btnIniciar.style.display = '';
                return;
            }

            navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 480 }, height: { ideal: 360 }, facingMode: facingMode },
                audio: false
            }).then(function(s) {
                stream = s;
                video.srcObject = s;
                deteccionActiva = true;
                btnIniciar.style.display = 'none';
                btnDetener.style.display = '';
                if (btnToggle) btnToggle.style.display = '';
                notificar('success', 'Cámara activa. Buscando rostros...');
                loopDetectar();
            }).catch(function(e) {
                notificar('error', 'No se pudo acceder a la cámara: ' + e.name + '.');
            });
        }).fail(function() {
            notificar('error', 'Error al obtener los rostros registrados.');
        });
    }

    function videoToFrameRect() {
        var vw = video.videoWidth, vh = video.videoHeight;
        var cw = video.clientWidth, ch = video.clientHeight;
        if (!vw || !vh || !cw || !ch) return null;
        var scale = Math.max(cw / vw, ch / vh);
        var dw = vw * scale, dh = vh * scale;
        var ox = (cw - dw) / 2, oy = (ch - dh) / 2;
        var fx1 = cw * 0.28, fy1 = ch * 0.17;
        var fx2 = cw * 0.72, fy2 = ch * 0.83;
        return {
            x: (fx1 - ox) / scale,
            y: (fy1 - oy) / scale,
            w: (fx2 - fx1) / scale,
            h: (fy2 - fy1) / scale
        };
    }

    async function loopDetectar() {
        if (!deteccionActiva) return;
        try {
            var detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            var ctx = overlay.getContext('2d');
            ctx.clearRect(0, 0, overlay.width, overlay.height);
            overlay.width = video.videoWidth;
            overlay.height = video.videoHeight;

            var detectedIds = {};
            var fr = videoToFrameRect();
            var caraEnZona = false;

            if (detections && detections.length) {
                detections.forEach(function(d) {
                    var best = null;
                    var bestDist = 0.6;
                    rostrosCurso.forEach(function(r) {
                        var dist = faceapi.euclideanDistance(d.descriptor, r.descriptor);
                        if (dist < bestDist) {
                            bestDist = dist;
                            best = r;
                        }
                    });

                    var box = d.detection.box;
                    if (fr) {
                        var cx = box.x + box.width / 2, cy = box.y + box.height / 2;
                        if (cx >= fr.x && cx <= fr.x + fr.w && cy >= fr.y && cy <= fr.y + fr.h) {
                            caraEnZona = true;
                        }
                    }
                    var color = best ? '#28a745' : '#dc3545';
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 3;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                    ctx.font = '14px Poppins, sans-serif';
                    ctx.fillStyle = color;
                    ctx.fillText(best ? best.nombres + ' ' + best.apellidos : 'Desconocido', box.x, box.y > 18 ? box.y - 6 : box.y + box.height + 18);

                    if (best) {
                        detectedIds[best.estudiante_id] = true;
                        marcarPresente(best.estudiante_id, best.nombres + ' ' + best.apellidos);
                    }
                });
            }

            if (frameEl) frameEl.classList.toggle('ok', caraEnZona);

            Object.keys(detectedIds).forEach(function(id) {
                var $picker = $('.asistencia-picker[data-student="' + id + '"]');
                if ($picker.length) {
                    $picker.closest('tr').css('background', 'rgba(40,167,69,.12)');
                }
            });
        } catch (e) {
            // ignorar errores puntuales de detección
        }

        timer = setTimeout(loopDetectar, 200);
    }

    function detener() {
        deteccionActiva = false;
        if (timer) clearTimeout(timer);
        if (stream) {
            stream.getTracks().forEach(function(t) { t.stop(); });
            stream = null;
        }
        video.srcObject = null;
        var ctx = overlay.getContext('2d');
        ctx.clearRect(0, 0, overlay.width, overlay.height);
        if (frameEl) frameEl.classList.remove('ok');
        btnDetener.style.display = 'none';
        btnIniciar.style.display = '';
        if (btnToggle) btnToggle.style.display = 'none';
        notificar('info', 'Detección detenida.');
    }

    function cambiarCamara() {
        facingMode = (facingMode === 'user') ? 'environment' : 'user';
        if (stream) {
            stream.getTracks().forEach(function(t) { t.stop(); });
        }
        navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 480 }, height: { ideal: 360 }, facingMode: facingMode },
            audio: false
        }).then(function(s) {
            stream = s;
            video.srcObject = s;
        }).catch(function(e) {
            notificar('error', 'No se pudo cambiar la cámara: ' + e.name);
            facingMode = (facingMode === 'user') ? 'environment' : 'user';
        });
    }

    btnIniciar.addEventListener('click', iniciar);
    btnDetener.addEventListener('click', detener);
    if (btnToggle) btnToggle.addEventListener('click', cambiarCamara);

    if (typeof faceapi !== 'undefined') {
        cargarModelos().then(function() {
            faceReady = true;
            notificar('success', 'Modelos listos. ' + (rostrosCurso.length ? rostrosCurso.length + ' rostros cargados.' : ''));
            btnIniciar.style.display = '';
        }).catch(function() {
            notificar('error', 'Error al cargar los modelos de reconocimiento.');
        });
    } else {
        var s = document.createElement('script');
        s.src = window.BASE_URL + 'js/face-api/face-api.min.js';
        s.onload = function() {
            cargarModelos().then(function() {
                faceReady = true;
                notificar('success', 'Modelos listos. Haga clic en Iniciar Detección.');
                btnIniciar.style.display = '';
            }).catch(function() {
                notificar('error', 'Error al cargar los modelos de reconocimiento.');
            });
        };
        s.onerror = function() {
            notificar('error', 'No se pudo cargar la librería de reconocimiento.');
        };
        document.head.appendChild(s);
    }
})();
JS
    )
);

$inlineScripts[] = str_replace(
    '__CURSO_ID__',
    (string) (int) $curso['id'],
    (<<<'JS'
(function() {
    var btn = document.getElementById('btnGuardarTolerancia');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var hora = document.getElementById('toleranciaHora').value;
        var minutos = parseInt(document.getElementById('toleranciaMinutos').value, 10) || 0;
        var cursoId = __CURSO_ID__;
        btn.disabled = true;
        var fd = new FormData();
        fd.append('_csrf_token', document.getElementById('formAsistencia').querySelector('input[name=_csrf_token]').value);
        fd.append('curso_id', cursoId);
        fd.append('hora_inicio', hora);
        fd.append('tolerancia_minutos', minutos);
        fetch(window.BASE_URL + 'docente/guardarTolerancia', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            if (window.AdminModals) AdminModals.showToast(res.success ? 'success' : 'error', res.message || 'Error al guardar.');
            if (res.success) {
                window.__recalcularTolerancia = true;
                setTimeout(function() { window.location.reload(); }, 600);
            }
        })
        .catch(function() {
            btn.disabled = false;
            if (window.AdminModals) AdminModals.showToast('error', 'Error de conexión.');
        });
    });
})();
JS
    )
);

$inlineScripts[] = <<<'JS'
(function() {
    var $btns = $('.btn-group[aria-label="Filtrar por marca de asistencia"] .btn');
    if (!$btns.length) return;
    $btns.on('click', function() {
        var $btn = $(this);
        var filtro = $btn.data('filtro');
        $btns.removeClass('active');
        $btn.addClass('active');
        $('table tbody tr[data-marcado]').each(function() {
            var $tr = $(this);
            var mostrar = (filtro === 'todos') || ($tr.data('marcado') === 1) === (filtro === 'marcados');
            $tr.toggle(mostrar);
        });
    });

    var btnMarcarTodos = document.getElementById('btnMarcarTodos');
    if (btnMarcarTodos && window.__marcarEstudiante) {
        btnMarcarTodos.addEventListener('click', function() {
            $('.asistencia-picker').each(function() {
                var $picker = $(this);
                if ($picker.find('input[type=hidden]').val() === 'pendiente') {
                    var estadoFinal = window.__aplicarTolerancia('P');
                    window.__marcarEstudiante($picker.data('student'), estadoFinal);
                }
            });
        });
    }

    var checkRecuperacion = document.getElementById('checkRecuperacion');
    var btnGuardar = document.getElementById('btnGuardarAsistencia');
    if (checkRecuperacion && btnGuardar) {
        var actualizarGuardar = function() {
            btnGuardar.disabled = !checkRecuperacion.checked;
            btnGuardar.title = checkRecuperacion.checked
                ? 'Guardar asistencia de clase de recuperación'
                : 'Debe marcar "Clase de recuperación" para guardar en un día no programado';
        };
        checkRecuperacion.addEventListener('change', actualizarGuardar);
        actualizarGuardar();
    }
})();
JS;

$inlineScripts[] = <<<'JS'
(function() {
    var aviso = document.getElementById('avisoAsistencia');
    if (!aviso || !aviso.scrollIntoView) return;
    try {
        aviso.scrollIntoView({ block: 'center', behavior: 'smooth' });
    } catch (e) {
        aviso.scrollIntoView();
    }
})();
JS;
?>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php $extraScripts = $extraScripts ?? []; $extraScripts[] = BASE_URL . 'js/admin_modals.js'; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
