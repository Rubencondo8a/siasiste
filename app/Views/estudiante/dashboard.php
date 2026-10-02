<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>Mis Notas</h1>
</section>

<section class="content">
    <?php $poderGeneral = studentPowerMode((float) $promedioGeneral, $nivelesMago ?? []); ?>
    <?php $costoDonante = costoDonacionMana($poderGeneral); $costoFrac = $costoDonante !== null ? '1/' . round(1 / (float) $costoDonante) : null; ?>

    <div class="dashboard-grid mb-3">
        <div class="dashboard-grid-mago">
            <div class="mago-card mago-<?= htmlspecialchars($poderGeneral['clave']) ?>">
                <span class="sparkles" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span>
                </span>
                <div class="mago-avatar">
                    <img src="<?= BASE_URL . $poderGeneral['avatar'] ?>" alt="<?= htmlspecialchars($poderGeneral['nombre']) ?>">
                </div>
                <div class="mago-info">
                    <div class="mago-label">Tu nivel de magia</div>
                    <h3><i class="<?= $poderGeneral['icono'] ?> mr-2"></i><?= htmlspecialchars($poderGeneral['nombre']) ?></h3>
                    <p><?= htmlspecialchars($poderGeneral['mensaje']) ?></p>
                    <span class="badge <?= $poderGeneral['clase'] ?>">Promedio: <?= number_format($promedioGeneral, 2) ?></span>
                    <div class="mago-mana" title="Poder">
                        <span class="mago-mana-count" style="color: <?= htmlspecialchars($poderGeneral['color_mana'] ?? '#ffc107') ?>;">
                            <i class="fas fa-bolt"></i> <?= (int) $poderGeneral['mana'] ?>
                        </span>
                        <div class="mago-mana-meter" style="--cs-color: <?= htmlspecialchars($poderGeneral['color_mana'] ?? '#ffc107') ?>;">
                            <?php for ($i = 0; $i < (int) $poderGeneral['mana']; $i++): ?>
                                <span class="mago-mana-seg"></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="mago-poderes">
                    <div class="mago-poderes-titulo"><i class="fas fa-star mr-1"></i>Poderes del nivel</div>
                    <?php foreach ($poderGeneral['poderes'] as $poder): ?>
                        <div class="mago-poder-item">
                            <i class="<?= $poder['icono'] ?>"></i>
                            <span><?= htmlspecialchars($poder['texto']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="dashboard-grid-side">
            <div class="info-box">
                <div class="info-box-content">
                    <span class="info-box-text">Promedio General</span>
                    <span class="info-box-number" style="color: <?= $promedioGeneral >= 13 ? '#0d6efd' : '#dc3545' ?>">
                        <?= number_format($promedioGeneral, 2) ?>
                    </span>
                </div>
            </div>
            <div class="card mana-side">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark">
                            <?= number_format($manaDisponible, 4) ?> <span class="text-muted">/ <?= $manaNivel ?> maná</span>
                        </span>
                        <span class="badge badge-warning"><?= number_format($porcentajeMana, 1) ?>%</span>
                    </div>
                    <div class="progress" style="height:7px;">
                        <div class="progress-bar bg-warning" style="width: <?= min(100, $porcentajeMana) ?>%;"></div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-arrow-up text-danger mr-1"></i>−<?= number_format($manaConsumido, 4) ?> al ayudar
                        &nbsp;&middot;&nbsp;
                        <i class="fas fa-arrow-down text-success mr-1"></i>+<?= number_format($manaRecibidoTotal, 4) ?> de compañeros
                    </small>
                </div>
            </div>
            <?php if (!empty($resumenesPuntos)): ?>
                <div class="card" style="border-left:4px solid #17a2b8;">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="font-weight-bold text-dark">
                                <i class="fas fa-gem text-info mr-1"></i>Mis Cristales
                            </span>
                            <a href="<?= BASE_URL ?>estudiante/tienda" class="btn btn-sm btn-info" style="padding:2px 10px;font-size:.78rem;">
                                <i class="fas fa-store mr-1"></i>Tienda
                            </a>
                        </div>
                        <?php foreach ($resumenesPuntos as $cursoId => $rp): ?>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.85rem;">
                                <span class="text-muted" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($rp['curso_nombre']) ?>">
                                    <?= htmlspecialchars($rp['curso_nombre']) ?>
                                </span>
                                <span>
                                    <span class="badge badge-warning mr-1" title="Puntos acumulados">⭐ <?= $rp['total_puntos'] ?></span>
                                    <span class="badge badge-info" title="Cristales disponibles">💎 <?= $rp['cristales'] ?></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-mana-tab" data-toggle="tab" href="#tab-mana" role="tab" aria-controls="tab-mana" aria-selected="true">
                        <i class="fas fa-hand-holding-heart mr-1 text-primary"></i> Maná y Compañerismo
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-calif-tab" data-toggle="tab" href="#tab-calif" role="tab" aria-controls="tab-calif" aria-selected="false">
                        <i class="fas fa-calendar-alt mr-1 text-info"></i> Calificaciones por Semana
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="dashboardTabsContent">
                <div class="tab-pane fade show active" id="tab-mana" role="tabpanel" aria-labelledby="tab-mana-tab">
                    <div class="row">
                <div class="col-lg-6">
                    <h5 class="font-weight-bold text-dark"><i class="fas fa-users mr-1"></i> Ayuda a un compañero</h5>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-info-circle mr-1"></i>Solo puedes enviar maná a niveles <strong>inferiores</strong> al tuyo:
                        <?php
                            $cadenaNiveles = [];
                            foreach (array_reverse($nivelesMago ?? []) as $nl) {
                                $inferiores = array_values(array_filter($nivelesMago ?? [], fn($x) => (int) $x['rango'] < (int) $nl['rango']));
                                $nombresInf = array_map(fn($x) => htmlspecialchars($x['nombre']), $inferiores);
                                if (!empty($nombresInf)) {
                                    $cadenaNiveles[] = htmlspecialchars($nl['nombre']) . ' → ' . implode(', ', $nombresInf);
                                }
                            }
                            echo implode(' &middot; ', $cadenaNiveles);
                        ?>
                    </small>
                    <?php if (!$puedeAyudar): ?>
                        <div class="callout callout-danger">
                            <h5>Tu nivel no permite transferir maná</h5>
                            <p>Como <strong>Iniciado</strong> solo puedes ver a tus compañeros Iniciados. Evoluciona a Aprendiz para poder ayudar a otros Iniciados, y sube de nivel para ampliar a quién puedes apoyar.</p>
                        </div>
                    <?php elseif ($esAprendiz): ?>
                        <div class="callout callout-warning">
                            <p class="mb-0">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Como <strong>Aprendiz</strong> puedes enviar maná a Iniciados, y cada una cuesta <strong>1/64</strong> de tu poder. Mago paga 1/128, Super Mago 1/256 y Merlín 1/512 de forma ilimitada.
                            </p>
                        </div>
                    <?php else: ?>
                        <small class="text-muted d-block mb-2">
                            <i class="fas fa-infinity mr-1"></i>Tienes transferencia ilimitada. Cada maná cuesta solo <strong><?= $costoFrac ?></strong> de tu poder.
                        </small>
                    <?php endif; ?>

                    <?php if (!empty($cursosConCompaneros)): ?>
                        <?php foreach ($cursosConCompaneros as $cursoC): ?>
                            <div class="mb-3">
                                <strong class="text-muted" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;">
                                    <?= htmlspecialchars($cursoC['curso_nombre']) ?> <span class="badge badge-info"><?= htmlspecialchars($cursoC['curso_codigo']) ?></span>
                                </strong>
                                <div class="companeros-grid">
                                    <?php foreach ($cursoC['companeros'] as $comp): ?>
                                        <?php $poderComp = $comp['poder']; ?>
                                        <?php $totalSemanas = count($comp['semanas'] ?? []); ?>
                                        <?php $ayudadas = count(array_filter($comp['semanas'] ?? [], fn($s) => $s['ya_ayude'])); ?>
                                        <?php $todoAyudado = $totalSemanas > 0 && $ayudadas === $totalSemanas; ?>
                                        <div class="companero-card <?= $todoAyudado ? 'companero-ayudado' : '' ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="font-weight-bold" style="font-size:.95rem;line-height:1.2;">
                                                    <?= htmlspecialchars($comp['apellidos'] . ', ' . $comp['nombres']) ?>
                                                </span>
                                                <span class="badge <?= $poderComp['clase'] ?>" style="font-size:.72rem;">
                                                    <i class="<?= $poderComp['icono'] ?> mr-1"></i><?= htmlspecialchars($poderComp['nombre']) ?>
                                                </span>
                                            </div>
                                            <?php if (!$comp['puede_recibir']): ?>
                                                <?php
                                                    $rangosNombres = [];
                                                    foreach (($nivelesMago ?? []) as $nl) {
                                                        $rangosNombres[(int) $nl['rango']] = $nl['nombre'];
                                                    }
                                                    $nivelRequerido = $rangosNombres[(int) ($poderComp['rango'] ?? 0) + 1] ?? null;
                                                ?>
                                                <div class="companero-bloqueado mt-2">
                                                    <i class="fas fa-lock mr-1"></i>
                                                    <?php if ($nivelRequerido === null): ?>
                                                        Es <strong><?= htmlspecialchars($poderComp['nombre']) ?></strong>, el nivel máximo: nadie puede enviarle maná.
                                                    <?php else: ?>
                                                        Es <strong><?= htmlspecialchars($poderComp['nombre']) ?></strong>. Necesitas ser <strong><?= $nivelRequerido ?></strong> para poder ayudarlo.
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="companero-semanas">
                                                    <?php foreach ($comp['semanas'] as $sem): ?>
                                                        <?php
                                                            $semId = (int) $sem['semana_id'];
                                                            $semNum = (int) $sem['numero_semana'];
                                                            $semaforo = $sem['semaforo'] ?? null;
                                                            $clickable = $semaforo && !empty($semaforo['necesita']) && !$sem['ya_ayude'] && !$sem['tiene_pendiente'];
                                                            $clase = 'sem-gris';
                                                            if ($sem['ya_ayude']) {
                                                                $clase = 'sem-ayudado';
                                                            } elseif ($sem['tiene_pendiente']) {
                                                                $clase = 'sem-pendiente';
                                                            } elseif ($semaforo) {
                                                                $clase = $semaforo['clase'];
                                                            }
                                                            $tituloSem = 'Semana ' . $semNum;
                                                            $confirmSem = $clickable && $esAprendiz
                                                                ? htmlspecialchars(json_encode('Como Aprendiz este maná en la Semana ' . $semNum . ' cuesta ' . ($costoFrac ?? '1/64') . ' de tu poder. ¿Enviarlo a ' . $comp['nombres'] . ' ' . $comp['apellidos'] . '?'), ENT_QUOTES, 'UTF-8')
                                                                : '';
                                                        ?>
                                                        <?php if ($clickable): ?>
                                                            <form method="post" action="<?= BASE_URL ?>estudiante/ayudarCompanero" class="d-inline">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="receptor_id" value="<?= $comp['id'] ?>">
                                                                <input type="hidden" name="curso_id" value="<?= $cursoC['curso_id'] ?>">
                                                                <input type="hidden" name="semana_id" value="<?= $semId ?>">
                                                                <button type="submit" class="semana-chip <?= $clase ?>" title="<?= $tituloSem ?>"
                                                                    <?php if ($clickable && $esAprendiz): ?>onclick="return confirmarChispa(event, <?= $confirmSem ?>, this.form);"<?php endif; ?>>
                                                                    S<?= $semNum ?>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="semana-chip <?= $clase ?>" style="cursor:not-allowed;" title="<?= $tituloSem ?>">S<?= $semNum ?></span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="callout callout-info">
                            <h5>Sin compañeros</h5>
                            <p>Aún no tienes compañeros a los que puedas apoyar con tu nivel actual.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-6">
                    <h5 class="font-weight-bold text-dark"><i class="fas fa-star mr-1 text-warning"></i> Maná que recibí</h5>
                    <?php if (!empty($solicitudesEstudiante)): ?>
                        <?php foreach ($solicitudesEstudiante as $sol): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-hat-wizard mr-1"></i>
                                <strong>¡Tienes oportunidad de examen</strong> en <?= htmlspecialchars($sol['curso_nombre']) ?>!
                                <small class="d-block mt-1">El docente ya fue notificado para registrar tu nueva nota.</small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($manaRecibido)): ?>
                        <?php foreach ($manaRecibido as $rec): ?>
                            <?php $pct = $rec['objetivo'] > 0 ? min(100, (int) round($rec['acumulado'] / $rec['objetivo'] * 100)) : 0; ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= htmlspecialchars($rec['curso_nombre']) ?> <span class="badge badge-info"><?= htmlspecialchars($rec['curso_codigo']) ?></span>
                                        <?php if (!empty($rec['semana_id'])): ?>
                                            <span class="badge badge-secondary">Semana <?= (int) $rec['numero_semana'] ?></span>
                                        <?php endif; ?>
                                    </strong>
                                    <small class="text-muted">
                                        <i class="fas fa-bolt text-warning"></i>
                                        <?= number_format((float) $rec['acumulado'], 0) ?> / <?= number_format((float) $rec['objetivo'], 0) ?>
                                    </small>
                                </div>
                                <div class="progress" style="height:10px;">
                                    <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width:<?= $pct ?>%;"></div>
                                </div>
                                <small class="text-muted">Se necesita <?= number_format((float) $rec['objetivo'], 0) ?> maná de compañeros para tu oportunidad de examen.</small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="callout callout-info">
                            <h5>Nadie te ha ayudado aún</h5>
                            <p>Tus compañeros pueden enviarte maná para que obtengas una oportunidad de examen.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
                </div>
                <div class="tab-pane fade" id="tab-calif" role="tabpanel" aria-labelledby="tab-calif-tab">
                    <div class="table-responsive">
            <?php if (!empty($semanasCalificadas)): ?>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Semana</th>
                            <th>Nombre</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Mi Promedio</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semanasCalificadas as $semana): ?>
                            <tr>
                                <td><?= $semana['numero_semana'] ?></td>
                                <td><span class="badge badge-info"><?= $semana['numero_semana'] ?></span></td>
                                <td><?= htmlspecialchars($semana['nombre']) ?></td>
                                <td><?= formatDate($semana['fecha_inicio']) ?></td>
                                <td><?= formatDate($semana['fecha_fin']) ?></td>
                                <td>
                                    <span class="badge <?= $semana['promedio'] >= 13 ? 'badge-primary' : 'badge-danger' ?> badge-lg">
                                        <?= number_format($semana['promedio'], 2) ?>
                                    </span>
                                    <?php if ($poderSemana = studentPowerMode((float) $semana['promedio'], $nivelesMago ?? [])): ?>
                                        <span class="badge <?= $poderSemana['clase'] ?>" style="font-size:.75rem;">
                                            <i class="<?= $poderSemana['icono'] ?> mr-1"></i><?= htmlspecialchars($poderSemana['nombre']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>estudiante/verSemana?semana_id=<?= $semana['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="callout callout-info">
                    <h5>Sin calificaciones</h5>
                    <p>Aún no tiene calificaciones registradas.</p>
                </div>
            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function confirmarChispa(event, mensaje, form) {
    event.preventDefault();
    if (typeof Swal === 'undefined') {
        form.submit();
        return false;
    }
    Swal.fire({
        title: '¿Enviar maná?',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}
</script>

<?php if (!empty($mostrarBienvenidaMago)): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/mago-welcome.css?v=<?= filemtime(ROOT_PATH . 'public/css/mago-welcome.css') ?>">
<?php
$frasesPorNivel = [
    'iniciado' => 'Debes esforzarte para sacar buenas notas y llegar al nivel de Merlín, donde tendrás poderes increíbles.',
    'aprendiz' => 'Estás avanzando muy bien. Sigue practicando para llegar a Merlín y desbloquear poderes aún más increíbles.',
    'mago'     => 'Tu magia se está fortaleciendo. Sigue esforzándote para llegar a Merlín, donde te esperan poderes legendarios.',
    'super'    => '¡Casi lo logras! Un esfuerzo más y alcanzarás el nivel de Merlín, con poderes espectaculares.',
    'merlin'   => '¡Eres un legendario Merlín! Ya tienes el poder máximo de la academia, sigue brillando.',
];
$fraseHabla = $frasesPorNivel[$poderGeneral['clave'] ?? 'iniciado'] ?? $frasesPorNivel['iniciado'];
?>
<script>
(function() {
    var mago = <?= json_encode([
        'estudiante' => trim(($_SESSION['user_nombres'] ?? '') . ' ' . ($_SESSION['user_apellidos'] ?? '')),
        'promedio'   => (float) $promedioGeneral,
        'frase'      => $fraseHabla,
        'nivel'      => [
            'clave'   => $poderGeneral['clave'] ?? 'iniciado',
            'nombre'  => $poderGeneral['nombre'] ?? 'Iniciado',
            'icono'   => $poderGeneral['icono'] ?? 'fas fa-feather-alt',
            'avatar'  => BASE_URL . ($poderGeneral['avatar'] ?? 'assets/img/avatar-iniciado.svg'),
            'mensaje' => $poderGeneral['mensaje'] ?? '',
            'mana' => (int) ($poderGeneral['mana'] ?? 1),
            'color'   => $poderGeneral['color_mana'] ?? '#ffc107',
            'poderes' => array_map(fn($p) => $p['texto'] ?? '', $poderGeneral['poderes'] ?? []),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    var intentos = 0;

    function mostrar() {
        if (typeof Swal === 'undefined') {
            if (++intentos < 60) { setTimeout(mostrar, 200); }
            return;
        }

    var poderesHtml = (mago.nivel.poderes || []).map(function(t) {
        return '<li><i class="fas fa-star"></i><span>' + t + '</span></li>';
    }).join('');

    var html =
        '<div class="mago-welcome mw-' + mago.nivel.clave + '">' +
            '<span class="mw-sparkle"></span><span class="mw-sparkle"></span><span class="mw-sparkle"></span>' +
            '<span class="mw-sparkle"></span><span class="mw-sparkle"></span><span class="mw-sparkle"></span>' +
            '<div class="mw-avatar"><img src="' + mago.nivel.avatar + '" alt="' + mago.nivel.nombre + '"></div>' +
            '<div class="mw-chip"><i class="fas fa-hat-wizard"></i> Nivel de magia</div>' +
            '<h2 class="mw-nivel"><i class="' + mago.nivel.icono + '"></i> ' + mago.nivel.nombre + '</h2>' +
            '<p class="mw-mensaje"><strong>¡Saludos, ' + mago.estudiante + '!</strong><br>' + mago.nivel.mensaje + '</p>' +
            '<div class="mw-stats">' +
                '<span class="mw-stat"><i class="fas fa-bolt"></i> ' + mago.nivel.mana + ' maná</span>' +
                '<span class="mw-stat"><i class="fas fa-chart-line"></i> Promedio: ' + mago.promedio.toFixed(2) + '</span>' +
            '</div>' +
            (poderesHtml ? '<ul class="mw-poderes">' + poderesHtml + '</ul>' : '') +
            '<button type="button" class="mw-btn">¡Adelante! <i class="fas fa-wand-sparkles"></i></button>' +
        '</div>';

    var hablo = false;
    function hablar(texto) {
        try {
            if (!('speechSynthesis' in window)) return;
            var msg = new SpeechSynthesisUtterance(texto);
            msg.lang = 'es-ES';
            msg.rate = 1;
            msg.pitch = 0.9;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(msg);
            hablo = true;
        } catch (e) {}
    }

    Swal.fire({
        width: 560,
        padding: 0,
        color: '#fff',
        background: 'transparent',
        html: html,
        showConfirmButton: false,
        allowOutsideClick: true,
        allowEscapeKey: true,
        customClass: {
            popup: 'mago-welcome-popup',
            htmlContainer: 'mago-welcome-container'
        },
        backdrop: 'linear-gradient(135deg, rgba(20,10,40,.92), rgba(70,10,90,.88))',
        didOpen: function() {
            hablar('¡Saludos ' + mago.estudiante + '! Te encuentras en el nivel ' + mago.nivel.nombre + '. ' + mago.frase);
        }
    }).then(function(result) {
        if (result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
            try { if (hablo) window.speechSynthesis.cancel(); } catch (e) {}
        }
    });

    var btn = document.querySelector('.mago-welcome .mw-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            Swal.close();
        });
    }
    }

    mostrar();
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
