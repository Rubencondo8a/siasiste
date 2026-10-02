<?php $pageTitle = $pageTitle ?? 'Resultado del examen'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-clipboard-check mr-2"></i><?= htmlspecialchars($examen['titulo']) ?></h1>
        <a href="<?= BASE_URL ?>examen/misExamenes" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Mis exámenes</a>
    </div>
</section>

<section class="content">
    <?php if ($intento['estado'] === 'en_curso'): ?>
        <div class="alert alert-info"><i class="fas fa-hourglass-half"></i> Su intento sigue en curso.
            <a href="<?= BASE_URL ?>examen/tomar?examen_id=<?= (int)$examen['id'] ?>">Volver al examen</a></div>
    <?php else: ?>
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box mb-0"><span class="info-box-icon bg-success"><i class="fas fa-star"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Nota final</span>
                    <span class="info-box-number"><?= number_format((float)($intento['nota'] ?? 0), 2) ?> / 20</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box mb-0"><span class="info-box-icon bg-primary"><i class="fas fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Puntaje</span>
                    <span class="info-box-number"><?= number_format((float)($intento['puntaje'] ?? 0), 1) ?> / <?= number_format($totales['puntos_total'], 1) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box mb-0"><span class="info-box-icon <?= $intento['estado'] === 'expirado' ? 'bg-warning' : 'bg-secondary' ?>"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Estado</span>
                    <span class="info-box-number"><?= $intento['estado'] === 'expirado' ? 'Expiró el tiempo' : 'Entregado' ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Revisión de respuestas</h3></div>
        <div class="card-body">
            <?php foreach ($preguntas as $i => $p): ?>
                <?php
                    $miRespuesta = $respuestas[(int)$p['id']] ?? null;
                    $correctas = array_filter($p['opciones'], fn($o) => (int)$o['es_correcta'] === 1);
                    $respondioBien = $miRespuesta && (int)$miRespuesta['correcta'] === 1;
                ?>
                <div class="mb-4 pb-3 border-bottom">
                    <strong><span class="badge badge-secondary mr-1"><?= $i + 1 ?></span> <?= htmlspecialchars($p['texto']) ?></strong>
                    <small class="text-muted">(<?= number_format((float)$p['puntos'], 1) ?> pts)</small>
                    <?php if ($p['tipo'] === 'abierta'): ?>
                        <?php $puntosOtorgados = $miRespuesta['puntos_otorgados'] ?? null; ?>
                        <div class="pl-3 mt-2">
                            <?php if ($miRespuesta && !empty($miRespuesta['respuesta_texto'])): ?>
                                <div class="border rounded p-2 bg-light">
                                    <i class="fas fa-keyboard text-muted mr-1"></i><?= nl2br(htmlspecialchars($miRespuesta['respuesta_texto'])) ?>
                                </div>
                            <?php else: ?>
                                <small class="text-muted"><i class="fas fa-minus"></i> Sin responder</small>
                            <?php endif; ?>
                            <span class="badge ml-1 <?= $puntosOtorgados !== null ? 'badge-success' : 'badge-warning' ?>">
                                <?= $puntosOtorgados !== null
                                    ? '+' . number_format((float)$puntosOtorgados, 1) . ' pts'
                                    : 'Pendiente de revisión del docente' ?>
                            </span>
                        </div>
                    <?php else: ?>
                    <div class="pl-3 mt-2">
                        <?php
                            $marcadasIds = [];
                            if ($miRespuesta && !empty($miRespuesta['opciones_marcadas'])) {
                                $marcadasIds = array_map('intval', json_decode($miRespuesta['opciones_marcadas'], true) ?: []);
                            } elseif ($miRespuesta && !empty($miRespuesta['opcion_id'])) {
                                $marcadasIds = [(int)$miRespuesta['opcion_id']];
                            }
                        ?>
                        <?php foreach ($p['opciones'] as $op): ?>
                            <?php
                                $esCorrecta = (int)$op['es_correcta'] === 1;
                                $esMiOpcion = in_array((int)$op['id'], $marcadasIds, true);
                            ?>
                            <div class="<?= $esCorrecta ? 'text-success font-weight-bold' : ($esMiOpcion ? 'text-danger' : 'text-muted') ?>">
                                <?php if ($esCorrecta): ?><i class="fas fa-check-circle"></i>
                                <?php elseif ($esMiOpcion): ?><i class="fas fa-times-circle"></i>
                                <?php else: ?><i class="far fa-circle"></i><?php endif; ?>
                                <?= htmlspecialchars($op['texto']) ?>
                                <?php if ($esMiOpcion): ?><small>(su respuesta)</small><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$miRespuesta): ?>
                            <small class="text-muted"><i class="fas fa-minus"></i> Sin responder</small>
                        <?php endif; ?>
                        <span class="badge ml-2 <?= $respondioBien ? 'badge-success' : ($miRespuesta ? 'badge-danger' : 'badge-light') ?>">
                            <?= $respondioBien ? '+' . number_format((float)$p['puntos'], 1) : '0' ?> pts
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
