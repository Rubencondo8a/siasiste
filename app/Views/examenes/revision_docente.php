<?php $pageTitle = $pageTitle ?? 'Revisión'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-search mr-2"></i><?= htmlspecialchars($examen['titulo']) ?></h1>
            <h5 class="text-muted mb-0"><?= htmlspecialchars($intento['estudiante_nombre']) ?>
                · Intento <?= (int)$intento['numero_intento'] ?>
                <?php if ($intento['estado'] === 'en_curso'): ?>
                    <span class="badge badge-warning">En curso</span>
                <?php else: ?>
                    <span class="badge badge-<?= $intento['estado'] === 'finalizado' ? 'success' : 'secondary' ?>"><?= ucfirst($intento['estado']) ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <a href="<?= BASE_URL ?>examen/resultados?examen_id=<?= (int)$examen['id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Resultados
        </a>
    </div>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="info-box mb-0"><span class="info-box-icon bg-primary"><i class="fas fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Puntaje actual</span>
                    <span class="info-box-number"><span id="lblPuntaje"><?= number_format((float)($intento['puntaje'] ?? 0), 1) ?></span> / <?= number_format($totales['puntos_total'], 1) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box mb-0"><span class="info-box-icon bg-success"><i class="fas fa-star"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Nota (20)</span>
                    <span class="info-box-number" id="lblNota"><?= number_format((float)($intento['nota'] ?? 0), 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($hayAbiertas): ?>
    <div class="alert alert-info py-2"><i class="fas fa-info-circle"></i> Las preguntas de <strong>respuesta escrita</strong> requieren que asigne puntos manualmente. La nota se recalcula al guardar.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php foreach ($preguntas as $i => $p): ?>
                <?php
                    $miRespuesta = $respuestas[(int)$p['id']] ?? null;
                    $esAbierta = $p['tipo'] === 'abierta';
                    $respondioBien = $miRespuesta && !$esAbierta && (int)$miRespuesta['correcta'] === 1;
                    $puntosOtorgados = $miRespuesta['puntos_otorgados'] ?? null;
                ?>
                <div class="mb-4 pb-3 border-bottom">
                    <strong><span class="badge badge-secondary mr-1"><?= $i + 1 ?></span> <?= htmlspecialchars($p['texto']) ?></strong>
                    <small class="text-muted">
                        (<?= number_format((float)$p['puntos'], 1) ?> pts)
                        <?php if ($esAbierta): ?><span class="badge badge-secondary ml-1">Respuesta escrita</span><?php endif; ?>
                    </small>
                    <div class="pl-3 mt-2">
                        <?php if ($esAbierta): ?>
                            <?php if ($miRespuesta && !empty($miRespuesta['respuesta_texto'])): ?>
                                <div class="border rounded p-2 bg-light mb-2">
                                    <?= nl2br(htmlspecialchars($miRespuesta['respuesta_texto'])) ?>
                                </div>
                                <?php if ($intento['estado'] !== 'en_curso'): ?>
                                <div class="form-inline">
                                    <label class="mr-2">Puntos a asignar (0 - <?= number_format((float)$p['puntos'], 1) ?>):</label>
                                    <input type="number" class="form-control form-control-sm mr-2 input-calificar"
                                           style="max-width:100px;" min="0" max="<?= (float)$p['puntos'] ?>" step="0.5"
                                           value="<?= $puntosOtorgados !== null ? number_format((float)$puntosOtorgados, 1, '.', '') : '' ?>"
                                           data-intento="<?= (int)$intento['id'] ?>" data-pregunta="<?= (int)$p['id'] ?>">
                                    <button type="button" class="btn btn-sm btn-primary btn-guardar-puntos"
                                            data-intento="<?= (int)$intento['id'] ?>" data-pregunta="<?= (int)$p['id'] ?>">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                    <span class="ml-2 text-success font-weight-bold resultado-fila"
                                          ><?= $puntosOtorgados !== null ? '+' . number_format((float)$puntosOtorgados, 1) . ' pts asignados' : '' ?></span>
                                </div>
                                <?php else: ?>
                                    <small class="text-muted">Podrá calificar cuando el intento finalice.</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-muted"><i class="fas fa-minus"></i> Sin responder (0 pts)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php
                                $marcadasIds = [];
                                if ($miRespuesta && !empty($miRespuesta['opciones_marcadas'])) {
                                    $marcadasIds = array_map('intval', json_decode($miRespuesta['opciones_marcadas'], true) ?: []);
                                } elseif ($miRespuesta && !empty($miRespuesta['opcion_id'])) {
                                    $marcadasIds = [(int)$miRespuesta['opcion_id']];
                                }
                                $correctasCountP = count(array_filter($p['opciones'], fn($o) => (int)$o['es_correcta'] === 1));
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
                                    <?php if ($esMiOpcion): ?><small>(su respuesta<?= $correctasCountP > 1 ? 's' : '' ?>)</small><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$miRespuesta): ?>
                                <small class="text-muted"><i class="fas fa-minus"></i> Sin responder</small>
                            <?php endif; ?>
                            <span class="badge ml-2 <?= $respondioBien ? 'badge-success' : ($miRespuesta ? 'badge-danger' : 'badge-light') ?>">
                                <?= $respondioBien ? '+' . number_format((float)$p['puntos'], 1) : '0' ?> pts
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.btn-guardar-puntos').forEach(btn => {
        btn.addEventListener('click', async () => {
            const fila = btn.closest('.form-inline');
            const input = fila.querySelector('.input-calificar');
            const puntos = parseFloat(input.value);
            if (isNaN(puntos) || puntos < 0) {
                Swal.fire('Valor inválido', 'Ingrese los puntos a asignar.', 'warning');
                return;
            }
            const fd = new FormData();
            fd.append('intento_id', btn.dataset.intento);
            fd.append('pregunta_id', btn.dataset.pregunta);
            fd.append('puntos', puntos);
            fd.append('_csrf_token', csrf);
            try {
                const res = await fetch(BASE_URL + 'examen/calificarAbierta', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    fila.querySelector('.resultado-fila').textContent = '+' + parseFloat(puntos).toFixed(1) + ' pts asignados';
                    document.getElementById('lblPuntaje').textContent = data.puntaje;
                    document.getElementById('lblNota').textContent = data.nota;
                    Swal.fire({ icon: 'success', title: data.message, timer: 900, showConfirmButton: false });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo guardar la calificación.', 'error');
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
