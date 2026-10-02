<?php
// Vista cargada dentro de modal (AdminModals) o como página mínima
$isAjaxView = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$modoActual = $examen['modo_disponibilidad'] ?? (
    !empty($examen['disponible_desde']) ? Examen::MODO_PROGRAMADO : Examen::MODO_INMEDIATO
);
if (!$isAjaxView):
    $pageTitle = $pageTitle ?? ($examen ? 'Editar Examen' : 'Nuevo Examen');
    require_once __DIR__ . '/../layouts/header.php';
    require_once __DIR__ . '/../partials/messages.php';
endif;
?>

<form id="frmExamen" method="post" action="<?= BASE_URL ?>examen/guardar">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) ($examen['id'] ?? 0) ?>">

    <div class="form-group">
        <label>Curso</label>
        <select id="selCurso" class="form-control" required>
            <option value="">-- Seleccione curso --</option>
            <?php foreach ($arbol as $cursoId => $semanas): ?>
                <?php
                $nombreCurso = '';
                foreach ($cursos as $c) {
                    if ((int)$c['id'] === (int)$cursoId) { $nombreCurso = $c['nombre']; break; }
                }
                ?>
                <option value="<?= (int) $cursoId ?>"><?= htmlspecialchars($nombreCurso) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Semana</label>
        <select id="selSemana" class="form-control" required disabled>
            <option value="">-- Primero elija un curso --</option>
        </select>
    </div>

    <div class="form-group">
        <label>Criterio (el examen registrará su nota aquí)</label>
        <select name="criterio_id" id="selCriterio" class="form-control" required disabled>
            <option value="">-- Primero elija una semana --</option>
        </select>
    </div>

    <div class="form-group">
        <label>Título del examen <span class="text-danger">*</span></label>
        <input type="text" name="titulo" class="form-control" required maxlength="200"
               value="<?= htmlspecialchars($examen['titulo'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Descripción / instrucciones</label>
        <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($examen['descripcion'] ?? '') ?></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Tipo</label>
            <select name="tipo" id="selTipo" class="form-control">
                <option value="personal" <?= ($examen['tipo'] ?? '') === 'personal' ? 'selected' : '' ?>>Personal (el alumno lo rinde cuando quiera)</option>
                <option value="en_vivo" <?= ($examen['tipo'] ?? '') === 'en_vivo' ? 'selected' : '' ?>>En vivo (el docente lo lanza en clase)</option>
            </select>
        </div>
        <div class="col-md-3 form-group">
            <label>Duración (minutos)</label>
            <input type="number" name="duracion_min" class="form-control" min="1" max="180"
                   value="<?= (int) ($examen['duracion_min'] ?? 30) ?>" required>
        </div>
        <div class="col-md-3 form-group">
            <label>Intentos permitidos</label>
            <input type="number" name="intentos_permitidos" class="form-control" min="1" max="10"
                   value="<?= (int) ($examen['intentos_permitidos'] ?? 1) ?>" required>
        </div>
    </div>

    <div id="bloqueVentanaPersonal" style="display:<?= ($examen['tipo'] ?? 'personal') === 'personal' ? 'block' : 'none' ?>;">
        <div class="form-group">
            <label>¿Cuándo podrán rendirlo los estudiantes?</label>
            <div class="row mx-n1" data-toggle="buttons">
                <div class="col-12 col-sm-6 px-1 pb-2 pb-sm-0">
                    <label class="btn btn-outline-primary btn-block <?= $modoActual === Examen::MODO_INMEDIATO ? 'active' : '' ?>" id="btnModoInmediato">
                        <input type="radio" name="modo_disponibilidad" value="inmediato" <?= $modoActual === Examen::MODO_INMEDIATO ? 'checked' : '' ?>>
                        <i class="fas fa-bolt"></i> Inmediato, sin fechas
                    </label>
                </div>
                <div class="col-12 col-sm-6 px-1 pb-2 pb-sm-0">
                    <label class="btn btn-outline-primary btn-block <?= $modoActual === Examen::MODO_PROGRAMADO ? 'active' : '' ?>" id="btnModoProgramado">
                        <input type="radio" name="modo_disponibilidad" value="programado" <?= $modoActual === Examen::MODO_PROGRAMADO ? 'checked' : '' ?>>
                        <i class="fas fa-calendar-alt"></i> Programado, por fecha y hora
                    </label>
                </div>
            </div>
        </div>

        <div id="bloqueVentanaFechas" style="display:<?= $modoActual === Examen::MODO_PROGRAMADO ? 'block' : 'none' ?>;">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Disponible desde <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="disponible_desde" class="form-control"
                           value="<?= !empty($examen['disponible_desde']) ? date('Y-m-d\TH:i', strtotime($examen['disponible_desde'])) : '' ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label>Disponible hasta <small>(opcional, sin límite si se deja vacío)</small></label>
                    <input type="datetime-local" name="disponible_hasta" class="form-control"
                           value="<?= !empty($examen['disponible_hasta']) ? date('Y-m-d\TH:i', strtotime($examen['disponible_hasta'])) : '' ?>">
                </div>
            </div>
            <small class="text-muted d-block mb-3">El cronómetro del alumno inicia cuando él abre el examen y dura lo indicado, sin exceder la fecha límite.</small>
        </div>

        <small class="text-muted d-block mb-3" id="hintInmediato">
            <i class="fas fa-bolt"></i> En cuanto active el examen, cualquier estudiante matriculado podrá tomarlo cuando quiera, sin fecha de apertura ni de cierre.
        </small>
    </div>

    <div class="modal-footer-border px-0 pb-0 pt-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar y agregar preguntas</button>
    </div>
</form>

<script>
(function () {
    const arbol = <?= json_encode($arbol, JSON_UNESCAPED_UNICODE) ?>;
    const selCurso = document.getElementById('selCurso');
    const selSemana = document.getElementById('selSemana');
    const selCriterio = document.getElementById('selCriterio');
    const selTipo = document.getElementById('selTipo');
    const ventana = document.getElementById('bloqueVentanaPersonal');
    const bloqueFechas = document.getElementById('bloqueVentanaFechas');
    const hintInmediato = document.getElementById('hintInmediato');
    const btnInmediato = document.getElementById('btnModoInmediato');
    const btnProgramado = document.getElementById('btnModoProgramado');

    function alternarModo() {
        const programado = btnProgramado.classList.contains('active');
        bloqueFechas.style.display = programado ? 'block' : 'none';
        hintInmediato.style.display = programado ? 'none' : 'block';
    }

    function cargarSemanas() {
        selSemana.innerHTML = '<option value="">-- Seleccione semana --</option>';
        selCriterio.innerHTML = '<option value="">-- Primero elija una semana --</option>';
        selCriterio.disabled = true;
        const datos = arbol[selCurso.value] || [];
        if (!datos.length) {
            selSemana.disabled = true;
            return;
        }
        datos.forEach((item, idx) => {
            const opt = document.createElement('option');
            opt.value = idx;
            opt.textContent = 'S' + item.semana.numero_semana + ': ' + item.semana.nombre;
            selSemana.appendChild(opt);
        });
        selSemana.disabled = false;
        cargarCriterios();
    }

    function cargarCriterios() {
        selCriterio.innerHTML = '<option value="">-- Seleccione criterio --</option>';
        const datos = arbol[selCurso.value] || [];
        const item = datos[parseInt(selSemana.value, 10)];
        if (!item) { selCriterio.disabled = true; return; }
        item.criterios.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            selCriterio.appendChild(opt);
        });
        selCriterio.disabled = false;
    }

    selCurso.addEventListener('change', cargarSemanas);
    selSemana.addEventListener('change', cargarCriterios);
    selTipo.addEventListener('change', () => {
        ventana.style.display = selTipo.value === 'personal' ? 'block' : 'none';
    });
    btnInmediato.addEventListener('click', () => setTimeout(alternarModo, 0));
    btnProgramado.addEventListener('click', () => setTimeout(alternarModo, 0));

    // Precarga en edición
    <?php if (!empty($examen)): ?>
    (function precargar() {
        const cursoId = '<?= (int) $examen['curso_id'] ?>';
        const semanaId = '<?= (int) $examen['semana_id'] ?>';
        const criterioId = '<?= (int) $examen['criterio_id'] ?>';
        if (![...selCurso.options].some(o => o.value === cursoId)) return;
        selCurso.value = cursoId;
        cargarSemanas();
        const datos = arbol[cursoId] || [];
        const idx = datos.findIndex(d => String(d.semana.id) === semanaId);
        if (idx >= 0) {
            selSemana.value = String(idx);
            cargarCriterios();
            selCriterio.value = criterioId;
        }
    })();
    <?php endif; ?>

    alternarModo();

    // Al guardar desde modal, recargar la página contenedora
    document.getElementById('frmExamen').addEventListener('submit', function () {
        setTimeout(() => window.location.reload(), 300);
    });
})();
</script>

<?php if (!$isAjaxView): require_once __DIR__ . '/../layouts/footer.php'; endif; ?>
