<?php $pageTitle = $pageTitle ?? $examen['titulo']; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="h4 mb-0"><i class="fas fa-file-signature mr-2"></i><?= htmlspecialchars($examen['titulo']) ?></h1>
        <div class="text-right">
            <div id="reloj" class="font-weight-bold <?= strtotime($deadlineIso) - time() < 300 ? 'text-danger' : 'text-primary' ?>"
                 style="font-size:2rem;">--:--</div>
            <small class="text-muted">Tiempo restante</small>
        </div>
    </div>
    <?php if ($examen['descripcion']): ?>
        <p class="text-muted mt-2 mb-0"><?= nl2br(htmlspecialchars($examen['descripcion'])) ?></p>
    <?php endif; ?>
</section>

<section class="content">
    <?php if (empty($preguntas)): ?>
        <div class="card"><div class="card-body text-center text-muted">Este examen no tiene preguntas.</div></div>
    <?php else: ?>
    <form id="frmExamenRunner" class="no-warn">
        <input type="hidden" name="_csrf_token" value="<?= generateCsrfToken() ?>">
        <?php foreach ($preguntas as $i => $p): ?>
        <div class="card mb-3 pregunta-card" data-pregunta="<?= (int)$p['id'] ?>">
            <div class="card-body">
                <h5 class="card-title">
                    <span class="badge badge-primary mr-2"><?= $i + 1 ?></span>
                    <?= htmlspecialchars($p['texto']) ?>
                    <small class="text-muted float-right">(<?= number_format((float)$p['puntos'], 1) ?> pts)</small>
                </h5>
                <div class="pl-4">
                    <?php if ($p['tipo'] === 'abierta'): ?>
                        <?php $textoPrevio = $respuestas[(int)$p['id']]['respuesta_texto'] ?? ''; ?>
                        <div class="form-group mb-1">
                            <textarea class="form-control respuesta-abierta" rows="3" maxlength="150"
                                      data-pregunta="<?= (int)$p['id'] ?>"
                                      placeholder="Escriba su respuesta (máximo 150 caracteres)"><?= htmlspecialchars($textoPrevio) ?></textarea>
                            <small class="text-muted contador" data-max="150">0/150</small>
                        </div>
                    <?php else: ?>
                    <?php
                        $correctasCount = count(array_filter($p['opciones'], fn($o) => (int)$o['es_correcta'] === 1));
                        $esMultiple = $correctasCount > 1;
                        $miRespuestaP = $respuestas[(int)$p['id']] ?? null;
                        $marcadas = [];
                        if ($miRespuestaP && !empty($miRespuestaP['opciones_marcadas'])) {
                            $marcadas = array_map('intval', json_decode($miRespuestaP['opciones_marcadas'], true) ?: []);
                        } elseif ($miRespuestaP && !empty($miRespuestaP['opcion_id'])) {
                            $marcadas = [(int)$miRespuestaP['opcion_id']];
                        }
                    ?>
                    <?php if ($esMultiple): ?>
                        <small class="text-info d-block mb-1"><i class="fas fa-check-double"></i> Seleccione todas las que considere correctas</small>
                    <?php endif; ?>
                    <?php foreach ($p['opciones'] as $j => $op): ?>
                    <?php $seleccionada = in_array((int)$op['id'], $marcadas, true); ?>
                    <div class="custom-control custom-<?= $esMultiple ? 'checkbox' : 'radio' ?> mb-2">
                        <input type="<?= $esMultiple ? 'checkbox' : 'radio' ?>" class="custom-control-input opcion-input<?= $esMultiple ? ' multiple' : '' ?>"
                               id="op_<?= (int)$op['id'] ?>"
                               name="pregunta_<?= (int)$p['id'] ?>"
                               value="<?= (int)$op['id'] ?>"
                               data-pregunta="<?= (int)$p['id'] ?>"
                               <?= $seleccionada ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="op_<?= (int)$op['id'] ?>">
                            <?= htmlspecialchars($op['texto']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="card border-success mb-3 sticky-top" style="top:60px;z-index:100;">
            <div class="card-body d-flex justify-content-between align-items-center py-3 flex-wrap">
                <span class="text-muted"><i class="fas fa-cloud-upload-alt mr-1"></i><span id="estadoGuardado">Las respuestas se guardan automáticamente</span></span>
                <button type="button" id="btnEntregar" class="btn btn-success btn-lg">
                    <i class="fas fa-paper-plane"></i> Entregar examen
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</section>

<script>
(function () {
    const intentoId = <?= (int) $intento['id'] ?>;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    // El conteo parte de un valor dado por el servidor y se resincroniza periódicamente
    let restanteSeg = Math.max(0, Math.floor((new Date('<?= $deadlineIso ?>').getTime() - Date.now()) / 1000));
    const reloj = document.getElementById('reloj');
    let entregado = false;

    function fmt(seg) {
        const h = Math.floor(seg / 3600), m = Math.floor((seg % 3600) / 60), s = seg % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function tick() {
        if (reloj) {
            reloj.textContent = fmt(restanteSeg);
            reloj.classList.toggle('text-danger', restanteSeg < 300);
        }
        if (restanteSeg <= 0 && !entregado) {
            expirar();
        }
        restanteSeg--;
    }

    function expirar() {
        entregado = true;
        clearInterval(intervalo);
        clearInterval(syncTimer);
        Swal.fire({
            title: 'Tiempo agotado',
            text: 'Se enviará lo que hayas respondido.',
            icon: 'warning', showConfirmButton: false,
            timer: 2500
        }).then(() => { window.location.href = BASE_URL + 'examen/resultado?id=' + intentoId; });
        const fd = new FormData();
        fd.append('intento_id', intentoId);
        fd.append('_csrf_token', csrf);
        fetch(BASE_URL + 'examen/finalizarIntento', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    }

    // Resincronización con el servidor cada 20s para mantener sincronía
    async function sincronizar() {
        if (entregado) return;
        try {
            const res = await fetch(BASE_URL + 'examen/restante?intento_id=' + intentoId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data.success) return;
            if (data.estado !== 'en_curso' || data.restante_segundos <= 0) {
                expirar();
                return;
            }
            // Corrige cualquier desvío de 1 segundo o más
            if (Math.abs(data.restante_segundos - restanteSeg) >= 1) {
                restanteSeg = data.restante_segundos;
            }
        } catch (e) { /* reintenta en el próximo ciclo */ }
    }

    tick();
    const intervalo = setInterval(tick, 1000);
    const syncTimer = setInterval(sincronizar, 5000);

    // Autoguardado al cambiar una respuesta (opción o texto)
    async function enviarRespuesta(preguntaId, payload) {
        if (entregado) return;
        const estado = document.getElementById('estadoGuardado');
        estado.textContent = 'Guardando...';
        const fd = new FormData();
        fd.append('intento_id', intentoId);
        fd.append('pregunta_id', preguntaId);
        Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
        fd.append('_csrf_token', csrf);
        try {
            const res = await fetch(BASE_URL + 'examen/responder', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                estado.textContent = '✓ Guardada ' + new Date().toLocaleTimeString();
                return true;
            } else if (data.expirado) {
                entregado = true;
                clearInterval(intervalo);
                Swal.fire('Tiempo finalizado', data.message, 'warning')
                    .then(() => window.location.href = BASE_URL + 'examen/resultado?id=' + intentoId);
            } else {
                estado.textContent = data.message || 'Error al guardar. Reintente.';
            }
        } catch (e) {
            estado.textContent = 'Sin conexión. Reintente marcando de nuevo.';
        }
        return false;
    }

    document.querySelectorAll('input[type=radio].opcion-input[data-pregunta]').forEach(input => {
        input.addEventListener('change', () => enviarRespuesta(input.dataset.pregunta, { opcion_id: input.value }));
    });

    // Preguntas de selección múltiple: enviar todas las marcadas
    const timersMultiples = {};
    document.querySelectorAll('input[type=checkbox].opcion-input.multiple[data-pregunta]').forEach(chk => {
        chk.addEventListener('change', () => {
            clearTimeout(timersMultiples[chk.dataset.pregunta]);
            timersMultiples[chk.dataset.pregunta] = setTimeout(() => {
                const marcadas = [...document.querySelectorAll(
                    'input[type=checkbox].opcion-input.multiple[data-pregunta="' + chk.dataset.pregunta + '"]:checked'
                )].map(i => i.value);
                enviarRespuesta(chk.dataset.pregunta, Object.fromEntries(marcadas.map((v, i) => ['opcion_ids[' + i + ']', v])));
            }, 300);
        });
    });

    const timersAbierta = {};
    document.querySelectorAll('textarea.respuesta-abierta[data-pregunta]').forEach(ta => {
        const contador = ta.closest('.form-group').querySelector('.contador');
        const max = parseInt(contador.dataset.max, 10);
        function actualizarContador() {
            contador.textContent = ta.value.length + '/' + max;
        }
        actualizarContador();
        ta.addEventListener('input', actualizarContador);
        ta.addEventListener('change', () => enviarRespuesta(ta.dataset.pregunta, { respuesta_texto: ta.value }));
    });

    // Entrega voluntaria
    document.getElementById('btnEntregar')?.addEventListener('click', () => {
        Swal.fire({
            title: '¿Entregar el examen?',
            text: 'No podrá modificar sus respuestas después.',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Sí, entregar', cancelButtonText: 'Seguir revisando'
        }).then(async r => {
            if (!r.isConfirmed) return;
            entregado = true;
            clearInterval(intervalo);
            const fd = new FormData();
            fd.append('intento_id', intentoId);
            fd.append('_csrf_token', csrf);
            try {
                const res = await fetch(BASE_URL + 'examen/finalizarIntento', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    entregado = false;
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                entregado = false;
                Swal.fire('Error', 'No se pudo entregar. Verifique su conexión.', 'error');
            }
        });
    });

    // Aviso si cierra sin entregar (el autoguardado protege las respuestas)
    window.addEventListener('beforeunload', (e) => {
        if (!entregado) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
